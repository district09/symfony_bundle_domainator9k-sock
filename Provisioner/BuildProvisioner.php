<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException;
use DigipolisGent\Domainator9k\CoreBundle\Provisioner\AbstractProvisioner;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class BuildProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class BuildProvisioner extends AbstractProvisioner
{

    private $dataValueService;
    private $taskLoggerService;
    private $apiService;
    private $entityManager;

    /**
     * BuildProvisioner constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        DataValueService $dataValueService,
        TaskLoggerService $taskLoggerService,
        ApiService $apiService,
        EntityManagerInterface $entityManager
    ) {
        $this->dataValueService = $dataValueService;
        $this->taskLoggerService = $taskLoggerService;
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
    }

    public function doRun()
    {
        $applicationEnvironment = $this->task->getApplicationEnvironment();
        $environment = $applicationEnvironment->getEnvironment();

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
                $polling = [];
                $polling['accounts'] = $this->createSockAccount($applicationEnvironment, $server);
                $polling['applications'] = $this->createSockApplication($applicationEnvironment);
                $polling['databases'] = $this->createSockDatabase($applicationEnvironment, $server);
            } catch (\Exception $ex) {
                $this->taskLoggerService->addFailedLogMessage($this->task, 'Provisioning failed.');
                throw new LoggedException('', 0, $ex);
            }
            try {
                $this->doPolling(array_filter($polling));
            } catch (\Exception $ex) {
                $this->taskLoggerService
                    ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                    ->addFailedLogMessage($this->task, 'Provisioning failed.');
                throw new LoggedException('', 0, $ex);
            }

            $this->taskLoggerService->addSuccessLogMessage($this->task, 'Provisioning succeeded.');
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param Server $server
     *
     * @return int
     *   The sock account id.
     */
    protected function createSockAccount(ApplicationEnvironment $applicationEnvironment, VirtualServer $server)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning account', 1);

        try {
            $application = $applicationEnvironment->getApplication();
            $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
            $sockServerId = $this->dataValueService->getValue($server, 'sock_server_id');

            // Check if the server exists.
            $this->apiService->getVirtualServer($sockServerId);

            if ($parentApplication) {
                $environment = $applicationEnvironment->getEnvironment();
                $parentApplicationEnvironment = $this->entityManager
                    ->getRepository(ApplicationEnvironment::class)
                    ->findOneBy(['application' => $parentApplication, 'environment' => $environment]);

                $sockAccountId = $this->dataValueService->getValue($parentApplicationEnvironment, 'sock_account_id');
                $username = $this->dataValueService->getValue($parentApplicationEnvironment, 'sock_ssh_user');

                if (!$sockAccountId || !$username) {
                    throw new \Exception('The parent application must be build first.');
                }

                $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $sockAccountId);
                $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);

                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Use parent account "%s".', $parentApplication->getName()),
                    2
                );

                return;
            }

            $username = $application->getNameCanonical();

            $this->taskLoggerService->addInfoLogMessage(
                $this->task,
                sprintf('Check if account "%s" exists', $username),
                2
            );

            $account = $this->apiService->findAccountByName($username, $sockServerId);
            $sshKeyIds = $this->dataValueService->getValue($applicationEnvironment, 'sock_ssh_key');

            if ($account) {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found account %s.', $account['id']),
                    2
                );
            } else {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    'No account found.',
                    2
                );

                $account = $this->apiService->createAccount($username, $sockServerId, $sshKeyIds);

                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Account %s created.', $account['id']),
                    2
                );
            }

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $account['id']);
            $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);

            return $account['id'];
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning account failed.', 2);

            throw $ex;
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     *
     * @return int
     *   The sock application id.
     */
    protected function createSockApplication(ApplicationEnvironment $applicationEnvironment)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning application', 1);

        try {
            $application = $applicationEnvironment->getApplication();
            $applicationName = $application->getNameCanonical();
            $technology = $this->dataValueService->getValue($application, 'sock_application_technology');
            $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

            // Check if the account exists.
            $this->apiService->getAccount($sockAccountId);

            $this->taskLoggerService->addInfoLogMessage(
                $this->task,
                sprintf('Check if application "%s" exists.', $applicationName),
                2
            );

            $application = $this->apiService->findApplicationByName($applicationName, $sockAccountId);

            if ($application) {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found application %s.', $application['id']),
                    2
                );
            } else {
                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    'No application found.',
                    2
                );

                $application = $this->apiService->createApplication(
                    $sockAccountId,
                    $applicationName,
                    [$applicationEnvironment->getDomain()],
                    'current',
                    $technology ? $technology : 'php-fpm'
                );

                $this->taskLoggerService->addInfoLogMessage(
                    $this->task,
                    sprintf('Application %s created.', $application['id']),
                    2
                );
            }

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', $application['id']);

            return $application['id'];
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning application failed.', 2);

            throw $ex;
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     *
     * @return int|null
     *   The sock database id, null if no database is required.
     */
    protected function createSockDatabase(ApplicationEnvironment $applicationEnvironment)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning database', 1);

        if (!$applicationEnvironment->getApplication()->isHasDatabase()) {
            $this->taskLoggerService->addInfoLogMessage($this->task, 'No database required.', 2);
            return;
        }

        try {
            $application = $applicationEnvironment->getApplication();
            $environment = $applicationEnvironment->getEnvironment();
            $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

            // Check if the account exists
            $this->apiService->getAccount($sockAccountId);

            $saveDatabase = false;

            if (!$databaseName = $applicationEnvironment->getDatabaseName()) {
                $databaseName = $application->getNameCanonical() . '_' . substr($environment->getName(), 0, 1);
                $saveDatabase = true;
            }

            if (!$databaseUser = $applicationEnvironment->getDatabaseUser()) {
                $databaseUser = $databaseName;
                $saveDatabase = true;
            } elseif (strlen($databaseUser) > 16) {
                $databaseUser = substr($databaseUser, 0, 16);
                $saveDatabase = true;
            }

            if (!$databasePassword = $applicationEnvironment->getDatabasePassword()) {
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

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_database_id', $database['id']);

            if ($saveDatabase) {
                $applicationEnvironment->setDatabaseUser($databaseUser);
                $applicationEnvironment->setDatabaseName($databaseName);
                $applicationEnvironment->setDatabasePassword($databasePassword);

                $this->entityManager->persist($applicationEnvironment);
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

    private function doPolling(array $polling)
    {
        $this->taskLoggerService->addInfoLogMessage(
            $this->task,
            'Waiting for changes to be applied.',
            2
        );

        $start = time();

        do {
            $count = 0;
            foreach ($polling as $type => $sockId) {
                $events = $this->apiService->getEvents($type, $sockId);
                $count += count($events);

                if ($count) {
                    break;
                }
            }

            if (!$count) {
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

    public function getName()
    {
        return 'Sock accounts, applications and databases';
    }
}
