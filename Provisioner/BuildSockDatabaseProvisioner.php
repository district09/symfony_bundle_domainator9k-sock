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
 * Class BuildSockDatabaseProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class BuildSockDatabaseProvisioner extends AbstractSockProvisioner
{
    const POLLING_TYPE = 'databases';

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
                $dbId = $this->createSockDatabase($appEnv);
                if ($dbId) {
                    $this->sockPoller->addPolling(static::POLLING_TYPE, $dbId, $this->task);
                    $this->ensurePollingProvisioner();
                }
            } catch (\Exception $ex) {
                $this->taskLoggerService->addFailedLogMessage($this->task, 'Provisioning sock database failed.');
                throw new LoggedException('', 0, $ex);
            }

            $this->taskLoggerService->addSuccessLogMessage($this->task, 'Provisioning sock database queued.');
        }
    }

    /**
     * @param ApplicationEnvironment $appEnv
     *
     * @return int|null
     *   The sock database id, null if no database is required.
     */
    protected function createSockDatabase(ApplicationEnvironment $appEnv)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning database', 1);

        if (!$appEnv->getApplication()->isHasDatabase()) {
            $this->taskLoggerService->addInfoLogMessage($this->task, 'No database required.', 2);
            return;
        }

        try {
            $application = $appEnv->getApplication();
            $environment = $appEnv->getEnvironment();
            $sockAccountId = $this->dataValueService->getValue($appEnv, 'sock_account_id');

            // Check if the account exists
            $this->apiService->getAccount($sockAccountId);

            $saveDatabase = false;

            if (!$databaseName = $appEnv->getDatabaseName()) {
                $databaseName = $application->getNameCanonical() . '_' . substr($environment->getName(), 0, 1);
                $saveDatabase = true;
            }

            if (!$databaseUser = $appEnv->getDatabaseUser()) {
                $databaseUser = $databaseName;
                $saveDatabase = true;
            } elseif (strlen($databaseUser) > 16) {
                $databaseUser = substr($databaseUser, 0, 16);
                $saveDatabase = true;
            }

            if (!$databasePassword = $appEnv->getDatabasePassword()) {
                $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
                $databasePassword = substr(str_shuffle($chars), 0, 15);
                $saveDatabase = true;
            }

            $this->taskLoggerService->addInfoLogMessage(
                $this->task,
                sprintf('Check if database "%s" exists', $databaseName),
                2
            );

            $database = $this->apiService->findDatabaseByName($databaseName, $sockAccountId);

            if ($database) {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found database %s.', $database['id']),
                    2
                );
            } else {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    'No database found.',
                    2
                );

                $database = $this->apiService->createDatabase(
                    $sockAccountId,
                    $databaseName,
                    $databaseUser,
                    $databasePassword
                );

                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Database %s created.', $database['id']),
                    2
                );
            }

            $login = $database['database_grants'][0]['login'];

            $this->taskLoggerService->addInfoLogMessage(
                $this->task,
                'Update access grants.',
                2
            );

            $this->apiService->removeDatabaseLogin($database['id'], $login);
            $this->apiService->addDatabaseLogin($database['id'], $databaseUser, $databasePassword);

            $this->dataValueService->storeValue($appEnv, 'sock_database_id', $database['id']);

            if ($saveDatabase) {
                $appEnv->setDatabaseUser($databaseUser);
                $appEnv->setDatabaseName($databaseName);
                $appEnv->setDatabasePassword($databasePassword);

                $this->entityManager->persist($appEnv);
                $this->entityManager->flush();
            }
            return $database['id'];
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning database failed.', 2);

            throw $ex;
        }
    }

    public function getName()
    {
        return 'Sock database';
    }
}
