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
use GuzzleHttp\Exception\ClientException;

/**
 * Class DestroySockApplicationProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class DestroySockApplicationProvisioner extends AbstractProvisioner
{

    protected $dataValueService;
    protected $taskLoggerService;
    protected $apiService;
    protected $entityManager;

    /**
     * DestroyProvisioner constructor.
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
                $this->destroySockApplication($appEnv);

                try {
                    $this->entityManager->persist($appEnv);
                    $this->entityManager->flush();
                } catch (\Exception $ex) {
                    $this->taskLoggerService->addWarningLogMessage($this->task, 'Could not remove local Sock data.');
                }

                $this->taskLoggerService->addSuccessLogMessage($this->task, 'Cleanup succeeded.');
            } catch (\Exception $ex) {
                $this->taskLoggerService->addFailedLogMessage($this->task, 'Cleanup failed.');
                throw new LoggedException('', 0, $ex);
            }
        }
    }

    /**
     * Destroy a sock application for a specific application environment.
     *
     * @param ApplicationEnvironment $appEnv
     *   The application environment to destroy the application for.
     *
     * @throws ClientException
     *   When something goes wrong while destroying the application.
     */
    protected function destroySockApplication(ApplicationEnvironment $appEnv)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Removing application', 1);

        if (!$applicationId = $this->dataValueService->getValue($appEnv, 'sock_application_id')) {
            $this->taskLoggerService->addInfoLogMessage($this->task, 'No application to remove.', 2);
            return;
        }

        try {
            $this->apiService->removeApplication($applicationId);

            $this->dataValueService->storeValue($appEnv, 'sock_application_id', null);

            $this->taskLoggerService->addSuccessLogMessage(
                $this->task,
                sprintf('Removed application %s.', $applicationId),
                2
            );
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Removing database failed.', 2);

            throw $ex;
        }
    }

    public function getName()
    {
        return 'Sock application';
    }
}
