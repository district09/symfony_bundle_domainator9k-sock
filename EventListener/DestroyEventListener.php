<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;


use DigipolisGent\Domainator9k\CoreBundle\Entity\Server;
use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;
use DigipolisGent\Domainator9k\CoreBundle\Event\DestroyEvent;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class BuildEventListener
 * @package DigipolisGent\Domainator9k\SockBundle\EventListener
 */
class DestroyEventListener
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
    public function onDestroy(DestroyEvent $event)
    {
        $applicationEnvironment = $event->getTask()->getApplicationEnvironment();
        $environment = $applicationEnvironment->getEnvironment();

        $servers = $this->entityManager->getRepository(Server::class)->findAll();

        foreach ($servers as $server) {

            try {
                if ($server->getEnvironment() != $environment) {
                    continue;
                }

                $manageSock = $this->dataValueService->getValue($server, 'manage_sock');

                if (!$manageSock) {
                    continue;
                }

                $accountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');
                if ($accountId) {
                    $this->apiService->removeAccount($accountId);
                    $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', null);
                }

                $applicationId = $this->dataValueService->getValue($applicationEnvironment, 'sock_application_id');
                if ($applicationId) {
                    $this->apiService->removeApplication($accountId);
                    $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', null);
                }

                $databaseId = $this->dataValueService->getValue($applicationEnvironment, 'sock_database_id');
                if ($databaseId) {
                    $this->apiService->removeDatabase($accountId);
                    $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', null);
                    $this->dataValueService->storeValue($applicationEnvironment, 'sock_database_id', null);
                }

                $applicationEnvironment->setDatabaseUser(null);
                $applicationEnvironment->setDatabaseName(null);
                $applicationEnvironment->setDatabasePassword(null);

                $this->entityManager->persist($applicationEnvironment);
                $this->entityManager->flush();

            } catch (ClientException $exception) {
                $this->taskLoggerService->addLine(
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
}