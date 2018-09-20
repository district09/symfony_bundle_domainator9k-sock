<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class BuildSockApplicationProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class BuildSockApplicationProvisioner extends AbstractSockProvisioner
{
    const POLLING_TYPE = 'applications';

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
        $appEnv = $this->task->getApplicationEnvironment();
        $environment = $appEnv->getEnvironment();

        /** @var VirtualServer[] $servers */
        $servers = $this->entityManager->getRepository(VirtualServer::class)->findAll();

        foreach ($servers as $server) {
            if ($server->getEnvironment() != $environment) {
                continue;
            }

            if (!$this->dataValueService->getValue($server, 'manage_sock')) {
                continue;
            }

            $this->taskLoggerService->addLogHeader(
                $this->task,
                sprintf('Sock server "%s"', $server->getName())
            );

            try {
                $appId = $this->createSockApplication($appEnv);
                if ($appId) {
                    $this->sockPoller->addPolling(static::POLLING_TYPE, $appId, $this->task);
                    $this->ensurePollingProvisioner();
                }
            } catch (\Exception $ex) {
                $this->taskLoggerService->addFailedLogMessage($this->task, 'Provisioning sock application failed.');
                throw new LoggedException('', 0, $ex);
            }

            $this->taskLoggerService->addSuccessLogMessage($this->task, 'Provisioning sock application queued.');
        }
    }

    /**
     * @param ApplicationEnvironment $appEnv
     *
     * @return int
     *   The sock application id.
     */
    protected function createSockApplication(ApplicationEnvironment $appEnv)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning application', 1);

        try {
            $application = $appEnv->getApplication();
            $applicationName = $application->getNameCanonical();
            $technology = $this->dataValueService->getValue($application, 'sock_application_technology');
            $sockAccountId = $this->dataValueService->getValue($appEnv, 'sock_account_id');

            // Check if the account exists.
            $this->apiService->getAccount($sockAccountId);

            $this->taskLoggerService->addInfoLogMessage(
                $this->task,
                sprintf('Check if application "%s" exists.', $applicationName),
                2
            );

            $application = $this->apiService->findApplicationByName($applicationName, $sockAccountId);
            $aliases = array_unique(
                array_merge(
                    [$appEnv->getDomain()],
                    $this->dataValueService->getValue($appEnv, 'sock_aliases')
                )
            );
            if ($application) {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found application %s.', $application['id']),
                    2
                );

                // Aliases to remove.
                $toRemove = array_diff($application['aliases'], $aliases);
                foreach ($toRemove as $remove) {
                    $this->apiService->removeApplicationAlias($application['id'], $remove);
                    $this->taskLoggerService->addInfoLogMessage(
                        $this->task,
                        sprintf('Removed alias %s.', $remove),
                        3
                    );
                }

                // Aliases to add.
                $toAdd = array_diff($aliases, $application['aliases']);
                foreach ($toAdd as $add) {
                    $this->apiService->addApplicationAlias($application['id'], $add);
                    $this->taskLoggerService->addInfoLogMessage(
                        $this->task,
                        sprintf('Added alias %s.', $remove),
                        3
                    );
                }
            } else {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    'No application found.',
                    2
                );

                $application = $this->apiService->createApplication(
                    $sockAccountId,
                    $applicationName,
                    [$appEnv->getDomain()],
                    'current',
                    $technology ? $technology : 'php-fpm'
                );

                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Application %s created.', $application['id']),
                    2
                );
            }

            $this->dataValueService->storeValue($appEnv, 'sock_application_id', $application['id']);

            return $application['id'];
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning application failed.', 2);

            throw $ex;
        }
    }

    public function getName()
    {
        return 'Sock application';
    }
}
