<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;


use DigipolisGent\Domainator9k\CoreBundle\Entity\AbstractApplication;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationServer;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Server;
use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;
use DigipolisGent\Domainator9k\CoreBundle\Service\BuildLoggerService;
use DigipolisGent\Domainator9k\CoreBundle\Tools\StringHelper;
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
    private $buildLoggerService;
    private $apiService;
    private $entityManager;

    /**
     * BuildEventListener constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        DataValueService $dataValueService,
        BuildLoggerService $buildLoggerService,
        ApiService $apiService,
        EntityManagerInterface $entityManager
    ) {
        $this->dataValueService = $dataValueService;
        $this->buildLoggerService = $buildLoggerService;
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param BuildEvent $event
     */
    public function onBuild(BuildEvent $event)
    {
        $applicationEnvironment = $event->getBuild()->getApplicationEnvironment();
        $environment = $applicationEnvironment->getEnvironment();

        foreach ($applicationEnvironment->getApplication()->getApplicationServers() as $applicationServer) {

            try {

                $server = $applicationServer->getServer();

                if ($server->getEnvironment() != $environment) {
                    continue;
                }

                $manageSock = $this->dataValueService->getValue($server, 'manage_sock');

                if (!$manageSock) {
                    continue;
                }

                $this->createSockAccount($applicationEnvironment, $applicationServer);
                $this->createSockApplication($applicationEnvironment, $applicationServer);

                if (!$applicationEnvironment->getApplication()->isHasDatabase()) {
                    continue;
                }

                $this->createSockDatabase($applicationEnvironment, $applicationServer);

            } catch (ClientException $exception) {
                $this->buildLoggerService->addLine(
                    sprintf(
                        'Error on updating sock with message "%s"',
                        $exception->getMessage()
                    )
                );

                continue;
            } catch (\Exception $exception) {
                die(var_dump($exception->getMessage()));
                // TODO : implement error handling
            }
        }
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param ApplicationServer $applicationServer
     */
    public function createSockAccount(
        ApplicationEnvironment $applicationEnvironment,
        ApplicationServer $applicationServer
    ) {

        $application = $applicationEnvironment->getApplication();
        $server = $applicationServer->getServer();

        $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
        $sockServerId = $this->dataValueService->getValue($server, 'sock_server_id');

        // Check if the server exists
        $this->apiService->getVirtualServer($sockServerId);

        if ($parentApplication) {
            $this->buildLoggerService->addLine(sprintf(
                'using existing account "%s" as parent on Sock Virtual Server %s',
                $parentApplication->getName(),
                $sockServerId
            ));

            return;
        }

        $username = $application->getNameCanonical();

        $this->buildLoggerService->addLine(sprintf(
            'requesting account "%s" on Sock Virtual Server %s',
            $username,
            $sockServerId
        ));

        $account = $this->apiService->findAccountByName($username, $sockServerId);
        $sshKeyIds = $this->dataValueService->getValue($applicationEnvironment, 'sock_ssh_key');

        if (!$account) {
            $this->buildLoggerService->addLine(sprintf(
                'account "%s" created on Sock Virtual Server %s',
                $username,
                $sockServerId
            ));

            $account = $this->apiService->createAccount($username, $sockServerId, $sshKeyIds);
        }

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', $account['id']);
        $this->dataValueService->storeValue($applicationEnvironment, 'sock_ssh_user', $username);
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param ApplicationServer $applicationServer
     */
    public function createSockApplication(
        ApplicationEnvironment $applicationEnvironment,
        ApplicationServer $applicationServer
    ) {
        $application = $applicationEnvironment->getApplication();

        $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
        $sockAccountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');

        // Check if the account exists
        $this->apiService->getAccount($sockAccountId);

        $applicationName = 'default';
        if ($parentApplication) {
            $applicationName = $parentApplication->getNameCanonical();
        }

        $this->buildLoggerService->addLine(sprintf(
            'requesting application "%s" for Sock Account %s',
            $applicationName,
            $sockAccountId
        ));

        $application = $this->apiService->findApplicationByName($applicationName, $sockAccountId);

        if (!$application) {
            $this->buildLoggerService->addLine(sprintf(
                'application "%s" created on for Sock Account %s',
                $applicationName,
                $sockAccountId
            ));

            $application = $this->apiService->createApplication(
                $sockAccountId,
                $applicationName,
                [$applicationEnvironment->getDomain()]
            );
        }

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', $application['id']);
    }

    /**
     * @param ApplicationEnvironment $applicationEnvironment
     * @param ApplicationServer $applicationServer
     */
    public function createSockDatabase(
        ApplicationEnvironment $applicationEnvironment,
        ApplicationServer $applicationServer
    ) {
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

        $this->buildLoggerService->addLine(sprintf(
            'requesting database "%s" for Sock Account %s',
            $databaseName,
            $sockAccountId
        ));

        $database = $this->apiService->findDatabaseByName($databaseName, $sockAccountId);

        if (!$database) {
            $this->buildLoggerService->addLine(sprintf(
                'database "%s" created on for Sock Account %s',
                $databaseName,
                $sockAccountId
            ));

            $database = $this->apiService->createDatabase(
                $sockAccountId,
                $databaseName,
                $databaseUser,
                $databasePassword
            );
        }

        $login = $database['database_grants'][0]['login'];

        $this->apiService->removeDatabaseLogin($database['id'], $login);
        $this->apiService->addDatabaseLogin($database['id'], $databaseUser, $databasePassword);

        $this->dataValueService->storeValue($applicationEnvironment, 'sock_database_id', $database['id']);

        $applicationEnvironment->setDatabaseUser($databaseUser);
        $applicationEnvironment->setDatabaseName($databaseName);
        $applicationEnvironment->setDatabasePassword($databasePassword);

        $this->entityManager->persist($applicationEnvironment);
        $this->entityManager->flush();
    }
}