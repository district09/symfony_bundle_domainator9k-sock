<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException;
use DigipolisGent\Domainator9k\CoreBundle\Provisioner\AbstractProvisioner;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class BuildSockAccountProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
class BuildSockAccountProvisioner extends AbstractProvisioner
{
    const POLLING_TYPE = 'accounts';

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
                $accountId = $this->createSockAccount($appEnv, $server);
                if ($accountId) {
                    $this->sockPoller->addPolling(static::POLLING_TYPE, $accountId, $this->task);
                }
            } catch (\Exception $ex) {
                $this->taskLoggerService->addFailedLogMessage($this->task, 'Provisioning sock account failed.');
                throw new LoggedException('', 0, $ex);
            }

            $this->taskLoggerService->addSuccessLogMessage($this->task, 'Provisioning sock account queued.');
        }
    }

    /**
     * @param ApplicationEnvironment $appEnv
     * @param Server $server
     *
     * @return int
     *   The sock account id.
     */
    protected function createSockAccount(ApplicationEnvironment $appEnv, VirtualServer $server)
    {
        $this->taskLoggerService->addLogHeader($this->task, 'Provisioning account', 1);

        try {
            $application = $appEnv->getApplication();
            $parentApplication = $this->dataValueService->getValue($application, 'parent_application');
            $sockServerId = $this->dataValueService->getValue($server, 'sock_server_id');

            // Check if the server exists.
            $this->apiService->getVirtualServer($sockServerId);

            if ($parentApplication) {
                $environment = $appEnv->getEnvironment();
                $parentAppEnv = $this->entityManager
                    ->getRepository(ApplicationEnvironment::class)
                    ->findOneBy(['application' => $parentApplication, 'environment' => $environment]);

                $sockAccountId = $this->dataValueService->getValue($parentAppEnv, 'sock_account_id');
                $username = $this->dataValueService->getValue($parentAppEnv, 'sock_ssh_user');

                if (!$sockAccountId || !$username) {
                    throw new \Exception('The parent application must be build first.');
                }

                $this->dataValueService->storeValue($appEnv, 'sock_account_id', $sockAccountId);
                $this->dataValueService->storeValue($appEnv, 'sock_ssh_user', $username);

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
            $sshKeyIds = $this->dataValueService->getValue($appEnv, 'sock_ssh_key');

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

            $this->dataValueService->storeValue($appEnv, 'sock_account_id', $account['id']);
            $this->dataValueService->storeValue($appEnv, 'sock_ssh_user', $username);

            return $account['id'];
        } catch (\Exception $ex) {
            $this->taskLoggerService
                ->addErrorLogMessage($this->task, $ex->getMessage(), 2)
                ->addFailedLogMessage($this->task, 'Provisioning account failed.', 2);

            throw $ex;
        }
    }

    public function getName()
    {
        return 'Sock account';
    }
}
