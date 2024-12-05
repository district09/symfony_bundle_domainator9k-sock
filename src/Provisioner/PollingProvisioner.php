<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException;
use DigipolisGent\Domainator9k\CoreBundle\Provisioner\AbstractProvisioner;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class PollingProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class PollingProvisioner extends AbstractProvisioner
{

    /**
     * @var ApiService
     */
    protected $apiService;

    /**
     * @var DataValueService
     */
    protected $dataValueService;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var SockPollerService
     */
    protected $sockPoller;

    /**
     *
     * @var TaskLoggerService
     */
    protected $taskLoggerService;

    /**
     * BuildProvisioner constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        DataValueService $dataValueService,
        TaskLoggerService $taskLoggerService,
        ApiService $apiService,
        EntityManagerInterface $entityManager,
        SockPollerService $sockPoller
    ) {
        $this->dataValueService = $dataValueService;
        $this->taskLoggerService = $taskLoggerService;
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
        $this->sockPoller = $sockPoller;
    }

    public function doRun()
    {
        try {
            $this->sockPoller->doPolling($this->task);
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning failed.');
            throw new LoggedException('', 0, $ex);
        }

        $this->taskLoggerService->addSuccessLogMessage($this->task, 'Provisioning succeeded.');
    }

    public function getName()
    {
        return 'Polling for sock accounts, applications and databases';
    }

    public function isSelectable()
    {
        return false;
    }
}
