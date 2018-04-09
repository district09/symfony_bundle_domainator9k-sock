<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
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

                $this->destroySockDatabase($applicationEnvironment);
                $this->destroySockApplication();
                $this->destroySockAccount($applicationEnvironment);

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
                // TODO : implement error handling
            }
        }
    }

    /**
     * Destroy a sock database for a specific application environment.
     *
     * @param ApplicationEnvironment $applicationEnvironment
     *   The application environment to destroy the database for.
     *
     * @throws ClientException
     *   When something goes wrong while destroying the database.
     */
    protected function destroySockDatabase(ApplicationEnvironment $applicationEnvironment)
    {
        $databaseId = $this->dataValueService->getValue($applicationEnvironment, 'sock_database_id');
        if (!$databaseId) {
            return;
        }
        $this->apiService->removeDatabase($databaseId);
        $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', null);
        $this->dataValueService->storeValue($applicationEnvironment, 'sock_database_id', null);
        $this->taskLoggerService->addLine(sprintf('Removed sock database %s.', $databaseId));
    }

    /**
     * Destroy a sock application for a specific application environment.
     *
     * @param ApplicationEnvironment $applicationEnvironment
     *   The application environment to destroy the application for.
     *
     * @throws ClientException
     *   When something goes wrong while destroying the application.
     */
    protected function destroySockApplication(ApplicationEnvironment $applicationEnvironment)
    {
      $applicationId = $this->dataValueService->getValue($applicationEnvironment, 'sock_application_id');
      if (!$applicationId) {
          return;
      }
      $this->apiService->removeApplication($applicationId);
      $this->dataValueService->storeValue($applicationEnvironment, 'sock_application_id', null);
      $this->taskLoggerService->addLine(sprintf('Removed sock application %s.', $applicationId));
    }

    /**
     * Destroy a sock account for a specific application environment.
     *
     * @param ApplicationEnvironment $applicationEnvironment
     *   The application environment to destroy the account for.
     *
     * @throws ClientException
     *   When something goes wrong while destroying the account.
     */
    protected function destroySockAccount(ApplicationEnvironment $applicationEnvironment)
    {
        $application = $applicationEnvironment->getApplication();
        $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
        // Only delete the account if this has no parent application.
        if (!$parentApplication) {
            $accountId = $this->dataValueService->getValue($applicationEnvironment, 'sock_account_id');
            if ($accountId) {
                $this->apiService->removeAccount($accountId);
                $this->dataValueService->storeValue($applicationEnvironment, 'sock_account_id', null);
                $this->taskLoggerService->addLine(sprintf('Removed sock account %s.', $accountId));
            }
        }
    }
}
