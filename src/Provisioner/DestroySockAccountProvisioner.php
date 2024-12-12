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
 * Class DestroySockAccountProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class DestroySockAccountProvisioner extends AbstractProvisioner
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
                $this->destroySockAccount($appEnv);

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
     * Destroy a sock account for a specific application environment.
     *
     * @param ApplicationEnvironment $appEnv
     *   The application environment to destroy the account for.
     *
     * @throws ClientException
     *   When something goes wrong while destroying the account.
     */
    protected function destroySockAccount(ApplicationEnvironment $appEnv)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Removing account', 1);

        $application = $appEnv->getApplication();

        if (!$accountId = $this->dataValueService->getValue($appEnv, 'sock_account_id')) {
            $this->taskLoggerService->addInfoLogMessage($this->task, 'No accoutn to remove.', 2);
            return;
        }

        if ($this->dataValueService->getValue($application, 'parent_application')) {
            $this->taskLoggerService->addInfoLogMessage($this->task, 'Using parent application account, not destroying account.', 2);
            return;
        }

        try {
            $this->apiService->removeAccount($accountId);

            $this->dataValueService->storeValue($appEnv, 'sock_account_id', null);
            $this->dataValueService->storeValue($appEnv, 'sock_ssh_user', null);

            $this->taskLoggerService->addSuccessLogMessage(
                $this->task,
                sprintf('Removed account %s.', $accountId),
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
        return 'Sock account';
    }
}
