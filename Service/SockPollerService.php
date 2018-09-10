<?php

namespace DigipolisGent\Domainator9k\SockBundle\Service;

use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;

class SockPollerService
{

    protected $taskLoggerService;
    protected $apiService;
    protected $polling;

    const POLLING_TYPE_ACCOUNT = 'accounts';
    const POLLING_TYPE_APPLICATION = 'applications';
    const POLLING_TYPE_DATABASE = 'databases';

    public function __construct(TaskLoggerService $taskLoggerService, ApiService $apiService)
    {
        $this->taskLoggerService = $taskLoggerService;
        $this->apiService = $apiService;
        $this->polling = [];
    }

    public function addPolling($type, $id, Task $task)
    {
        $this->polling[$task->getId()][$type] = $id;
    }

    public function doPolling(Task $task)
    {
        if (!isset($this->polling[$task->getId()])) {
            return;
        }
        $this->taskLoggerService->addInfoLogMessage(
            $task,
            'Waiting for changes to be applied.',
            2
        );

        $start = time();

        do {
            $events = false;
            foreach ($this->polling[$task->getId] as $type => $sockId) {
                if ($events = $this->apiService->getEvents($type, $sockId)) {
                    break;
                }
            }

            if (!$events) {
                break;
            }

            if ((time() - $start) >= 600) {
                throw new \Exception(
                    sprintf(
                        'Timeout, waited more then 10 minutes while polling for %s #%s.',
                        $type,
                        $sockId
                    )
                );
            }

            sleep(5);
        } while (true);
    }
}
