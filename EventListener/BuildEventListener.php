<?php

namespace DigipolisGent\Domainator9k\SockBundle\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationServer;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Event\AbstractEvent;
use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class BuildEventListener
 *
 * @package DigipolisGent\Domainator9k\SockBundle\EventListener
 */
class BuildEventListener
{

    private $dataValueService;
    private $taskService;
    private $apiService;
    private $entityManager;
    private $task;

    /**
     * BuildEventListener constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        DataValueService $dataValueService,
        TaskService $taskService,
        ApiService $apiService,
        EntityManagerInterface $entityManager
    ) {
        $this->dataValueService = $dataValueService;
        $this->taskService = $taskService;
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param BuildEvent $event
     */
    public function onBuild(BuildEvent $event)
    {
        $this->task = $event->getTask();

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

            $this->taskService->addLogHeader(
                $this->task,
                sprintf('Sock server "%s"', $server->getName())
            );

            try {
                $this->createSockAccount($applicationEnvironment, $server);
                $this->createSockApplication($applicationEnvironment);
                $this->createSockDatabase($applicationEnvironment, $server);

                $this->taskService->addSuccessLogMessage($this->task, 'Provisioning succeeded.');
            } catch (\Exception $ex) {
                $this->taskService->addFailedLogMessage($this->task, 'Provisioning failed.');
                $event->stopPropagation();
                return;
            }
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param Server $server
     */
    protected function createSockAccount(ApplicationEnvironment $applicationEnvironment, VirtualServer $server)
    {
        $this->taskService->addLogHeader($this->task, 'Provisioning account', 1);

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

                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Use parent account "%s".', $parentApplication->getName()),
                    2
                );

                return;
            }

            $username = $application->getNameCanonical();

            $this->taskService->addInfoLogMessage(
                $this->task,
                sprintf('Check if account "%s" exists', $username)
            );

            $account = $this->apiService->findAccountByName($username, $sockServerId);
            $sshKeyIds = $this->dataValueService->getValue($applicationEnvironment, 'sock_ssh_key');

            if ($account) {
                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found account %s.', $account['id']),
                    2
                );
            } else {
                $this->taskService->addInfoLogMessage(
                    $this->task,
                    'No account found.',
                    2
                );

                $account = $this->apiService->createAccount($username, $sockServerId, $sshKeyIds);

                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Account %s created.', $account['id']),
                    2
                );
            }

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $account['id']);
            $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);

            $this->doPolling('accounts', $account['id']);
        } catch (\Exception $ex) {
            $this->taskService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning account failed.', 2);

            throw $ex;
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     */
    protected function createSockApplication(ApplicationEnvironment $applicationEnvironment)
    {
        $this->taskService->addLogHeader($this->task, 'Provisioning application', 1);

        try {
            $application = $applicationEnvironment->getApplication();
            $applicationName = $application->getNameCanonical();
            $technology = $this->dataValueService->getValue($application, 'sock_application_technology');
            $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

            // Check if the account exists.
            $this->apiService->getAccount($sockAccountId);

            $this->taskService->addInfoLogMessage(
                $this->task,
                sprintf('Check if application "%s" exists.', $applicationName)
            );

            $application = $this->apiService->findApplicationByName($applicationName, $sockAccountId);

            if ($application) {
                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found application %s.', $application['id']),
                    2
                );
            } else {
                $this->taskService->addInfoLogMessage(
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

                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Application %s created.', $application['id']),
                    2
                );
            }

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', $application['id']);

            $this->doPolling('applications', $application['id']);
        } catch (\Exception $ex) {
            $this->taskService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning application failed.', 2);

            throw $ex;
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     */
    protected function createSockDatabase(ApplicationEnvironment $applicationEnvironment)
    {
        $this->taskService->addLogHeader($this->task, 'Provisioning database', 1);

        if (!$applicationEnvironment->getApplication()->isHasDatabase()) {
            $this->taskService->addInfoLogMessage($this->task, 'No database required.', 2);
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

            $this->taskService->addInfoLogMessage(
                $this->task,
                sprintf('Check if database "%s" exists', $databaseName)
            );

            $database = $this->apiService->findDatabaseByName($databaseName, $sockAccountId);

            if ($database) {
                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Found database %s.', $database['id']),
                    2
                );
            } else {
                $this->taskService->addInfoLogMessage(
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

                $this->taskService->addInfoLogMessage(
                    $this->task,
                    sprintf('Database %s created.', $database['id']),
                    2
                );
            }

            $login = $database['database_grants'][0]['login'];

            $this->taskService->addInfoLogMessage(
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

            $this->doPolling('databases', $database['id']);
        } catch (\Exception $ex) {
            $this->taskService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning database failed.', 2);

            throw $ex;
        }
    }

    private function doPolling($type, $id)
    {
        $this->taskService->addInfoLogMessage($this->task, 'Waiting for changes to be applied.');

        $start = time();
        $events = $this->apiService->getEvents($type, $id);

        while (count($events)) {
            $events = $this->apiService->getEvents($type, $id);
            sleep(5);

            if ((time() - $start) >= 600) {
                throw new \Exception('Timeout, waited more then 10 minutes.');
            }
        }
    }
}
