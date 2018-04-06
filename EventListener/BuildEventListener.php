<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationServer;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class BuildEventListener
 * @package DigipolisGent\Domainator9k\SockBundle\EventListener
 */
class BuildEventListener
{

    private $dataValueService;
    private $taskLoggerService;
    private $apiService;
    private $entityManager;

    /**
     * BuildEventListener constructor.
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

    /**
     * @param BuildEvent $event
     */
    public function onBuild(BuildEvent $event)
    {
        if ($event->getTask()->getStatus() == Task::STATUS_FAILED) {
            return;
        }

        $applicationEnvironment = $event->getTask()->getApplicationEnvironment();
        $environment = $applicationEnvironment->getEnvironment();

        $servers = $this->entityManager->getRepository(VirtualServer::class)->findAll();

        foreach ($servers as $server) {
            try {
                if ($server->getEnvironment() != $environment) {
                    continue;
                }

                $manageSock = $this->dataValueService->getValue($server, 'manage_sock');

                if (!$manageSock) {
                    continue;
                }

                $this->createSockAccount($applicationEnvironment, $server);
                $this->createSockApplication($applicationEnvironment);

                if (!$applicationEnvironment->getApplication()->isHasDatabase()) {
                    continue;
                }

                $this->createSockDatabase($applicationEnvironment, $server);
            } catch (ClientException $exception) {

                $this->taskLoggerService->addLine(
                    sprintf(
                        'Error on updating sock with message "%s"',
                        $exception->getMessage()
                    )
                );

                $this->taskLoggerService->endTask();
                return;
            } catch (\Exception $exception) {
                $this->taskLoggerService->endTask();
            }
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param Server $server
     */
    public function createSockAccount(ApplicationEnvironment $applicationEnvironment, VirtualServer $server)
    {
        $application = $applicationEnvironment->getApplication();
        $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
        $sockServerId = $this->dataValueService->getValue($server, 'sock_server_id');

        // Check if the server exists.
        $this->apiService->getVirtualServer($sockServerId);

        if ($parentApplication) {
            $environment = $applicationEnvironment->getEnvironment();
            $parentApplicationEnvironment = $this->entityManager
                ->getRepository(ApplicationEnvironment::class)
                ->findOneBy(['application' => $parentApplication,'environment' => $environment]);

            $sockAccountId = $this->dataValueService->getValue($parentApplicationEnvironment,'sock_account_id');
            $username = $this->dataValueService->getValue($parentApplicationEnvironment,'sock_ssh_user');

            if(!$sockAccountId || !$username){
                $this->taskLoggerService->addLine(sprintf(
                    'The parent application was never build, the sock account is not available yet.'));
                throw new \Exception('The parent application was never build, the sock account is not available yet.');
            }

            $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $sockAccountId);
            $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);

            $this->taskLoggerService->addLine(sprintf(
                'Using existing account "%s" as parent on Sock Virtual Server %s.',
                $parentApplication->getName(),
                $sockServerId
            ));

            return;
        }

        $username = $application->getNameCanonical();

        $this->taskLoggerService->addLine(sprintf(
            'Requesting account "%s" on Sock Virtual Server %s.',
            $username,
            $sockServerId
        ));

        $account = $this->apiService->findAccountByName($username, $sockServerId);
        $sshKeyIds = $this->dataValueService->getValue($applicationEnvironment, 'sock_ssh_key');

        $account
            ? $this->taskLoggerService->addLine(sprintf(
            'Account "%s" found as sock account with id %s.',
            $username,
            $account['id']
        ))
            : $this->taskLoggerService->addLine(sprintf(
            'Account "%s" not found, we\'ll have to create one.',
            $username
        ));

        if (!$account) {
            $account = $this->apiService->createAccount($username, $sockServerId, $sshKeyIds);
            $this->taskLoggerService->addLine(sprintf(
                'Account "%s" with id %s created on Sock Virtual Server %s.',
                $username,
                $account['id'],
                $sockServerId
            ));
        }

        $sockAccountId = $account['id'];

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $sockAccountId);
        $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);

        $this->taskLoggerService->addLine('Polling');
        // Implemented polling
        $events = $this->apiService->getEvents('accounts', $sockAccountId);
        while (count($events)) {
            $events = $this->apiService->getEvents('accounts', $sockAccountId);
            sleep(5);
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     */
    public function createSockApplication(ApplicationEnvironment $applicationEnvironment)
    {
        $application = $applicationEnvironment->getApplication();

        $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

        // Check if the account exists.
        $this->apiService->getAccount($sockAccountId);

        $applicationName = $application->getNameCanonical();
        $technology = $this->dataValueService->getValue($application, 'sock_application_technology');

        $this->taskLoggerService->addLine(sprintf(
            'Requesting application "%s" for Sock Account %s.',
            $applicationName,
            $sockAccountId
        ));

        $application = $this->apiService->findApplicationByName($applicationName, $sockAccountId);

        $application
            ? $this->taskLoggerService->addLine(sprintf(
            'Application "%s" for sock account %s found as sock application with id %s.',
            $applicationName,
            $sockAccountId,
            $application['id']
        ))
            : $this->taskLoggerService->addLine(sprintf(
            'Application "%s" for sock account %s not found, we\'ll have to create one.',
            $applicationName,
            $sockAccountId
        ));

        if (!$application) {
            $application = $this->apiService->createApplication(
                $sockAccountId,
                $applicationName,
                [$applicationEnvironment->getDomain()],
                'current',
                $technology ? $technology : 'php-fpm'
            );

            $this->taskLoggerService->addLine(sprintf(
                'Application "%s" created on for Sock Account %s.',
                $applicationName,
                $sockAccountId
            ));
        }

        $applicationId = $application['id'];

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', $applicationId);

        $this->taskLoggerService->addLine('Polling');
        // Implemented polling
        $events = $this->apiService->getEvents('applications', $applicationId);
        while (count($events)) {
            $events = $this->apiService->getEvents('applications', $applicationId);
            sleep(5);
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     */
    public function createSockDatabase(ApplicationEnvironment $applicationEnvironment)
    {
        $application = $applicationEnvironment->getApplication();
        $environment = $applicationEnvironment->getEnvironment();

        $databaseName = $applicationEnvironment->getDatabaseName();
        $databaseUser = $applicationEnvironment->getDatabaseUser();
        $databasePassword = $applicationEnvironment->getDatabasePassword();

        if (!$databaseName) {
            $databaseName = $application->getNameCanonical() . '_' . substr($environment->getName(), 0, 1);
        }

        if (!$databaseUser) {
            $databaseUser = $databaseName;
        }

        if (!$databasePassword) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
            $databasePassword = substr(str_shuffle($chars), 0, 15);
        }

        $databaseUser = substr($databaseUser, 0, 15);

        $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

        // Check if the account exists
        $this->apiService->getAccount($sockAccountId);

        $this->taskLoggerService->addLine(sprintf(
            'Requesting database "%s" for Sock Account %s.',
            $databaseName,
            $sockAccountId
        ));

        $database = $this->apiService->findDatabaseByName($databaseName, $sockAccountId);

        $database
            ? $this->taskLoggerService->addLine(sprintf(
            'Database "%s" for sock account %s found as sock database with id %s.',
            $databaseName,
            $sockAccountId,
            $database['id']
        ))
            : $this->taskLoggerService->addLine(sprintf(
            'Database "%s" for sock account %s not found, we\'ll have to create one.',
            $databaseName,
            $sockAccountId
        ));

        if (!$database) {
            $database = $this->apiService->createDatabase(
                $sockAccountId,
                $databaseName,
                $databaseUser,
                $databasePassword
            );

            $this->taskLoggerService->addLine(sprintf(
                'Database "%s" created for Sock Account %s.',
                $databaseName,
                $sockAccountId
            ));
        }

        $login = $database['database_grants'][0]['login'];

        $this->apiService->removeDatabaseLogin($database['id'], $login);
        $this->apiService->addDatabaseLogin($database['id'], $databaseUser, $databasePassword);

        $databaseId = $database['id'];

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_database_id', $databaseId);

        $applicationEnvironment->setDatabaseUser($databaseUser);
        $applicationEnvironment->setDatabaseName($databaseName);
        $applicationEnvironment->setDatabasePassword($databasePassword);

        $this->entityManager->persist($applicationEnvironment);
        $this->entityManager->flush();

        $this->taskLoggerService->addLine('Polling');
        // Implemented polling
        $events = $this->apiService->getEvents('databases', $databaseId);
        while (count($events)) {
            $events = $this->apiService->getEvents('databases', $databaseId);
            sleep(5);
        }
    }
}
