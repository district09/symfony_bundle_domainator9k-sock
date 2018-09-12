<?php

namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\PollingProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;

class PollingProvisionerTest extends AbstractProvisionerTest
{

    public function testOnBuild()
    {
        $prodEnvironment = new Environment();
        $prodEnvironment->setName('prod');
        $prodEnvironment->setProd(true);

        $uatEnvironment = new Environment();
        $uatEnvironment->setName('uat');
        $uatEnvironment->setProd(true);

        $servers = new ArrayCollection();

        $serverOne = new VirtualServer();
        $serverOne->setEnvironment($uatEnvironment);
        $servers->add($serverOne);

        $serverTwo = new VirtualServer();
        $serverTwo->setEnvironment($prodEnvironment);
        $servers->add($serverTwo);

        $serverThree = new VirtualServer();
        $serverThree->setEnvironment($prodEnvironment);
        $servers->add($serverThree);

        $application = new FooApplication();
        $application->setHasDatabase(true);

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setEnvironment($prodEnvironment);
        $applicationEnvironment->setApplication($application);

        $entityManagerFunctions = [];

        $dataValueServiceFunctions = [];

        $apiServiceFunctions = [];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setProvisioners([BuildSockAccountProvisioner::class]);
        $task->setApplicationEnvironment($applicationEnvironment);

        $sockPoller = $this->getMockBuilder(SockPollerService::class)->setConstructorArgs([$taskLoggerService, $apiService])->getMock();
        $sockPoller->expects($this->once())->method('doPolling')->with($task);

        $provisioner = new PollingProvisioner($dataValueService, $taskLoggerService, $apiService, $entityManager, $sockPoller);
        $provisioner->setTask($task);
        $provisioner->run();
    }

    /**
     * @expectedException \DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException
     */
    public function testPollingFailed()
    {
        $prodEnvironment = new Environment();
        $prodEnvironment->setName('prod');
        $prodEnvironment->setProd(true);

        $uatEnvironment = new Environment();
        $uatEnvironment->setName('uat');
        $uatEnvironment->setProd(true);

        $servers = new ArrayCollection();

        $serverOne = new VirtualServer();
        $serverOne->setEnvironment($uatEnvironment);
        $servers->add($serverOne);

        $serverTwo = new VirtualServer();
        $serverTwo->setEnvironment($prodEnvironment);
        $servers->add($serverTwo);

        $serverThree = new VirtualServer();
        $serverThree->setEnvironment($prodEnvironment);
        $servers->add($serverThree);

        $application = new FooApplication();
        $application->setHasDatabase(true);

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setEnvironment($prodEnvironment);
        $applicationEnvironment->setApplication($application);

        $entityManagerFunctions = [];

        $dataValueServiceFunctions = [];

        $apiServiceFunctions = [];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setProvisioners([BuildSockAccountProvisioner::class]);
        $task->setApplicationEnvironment($applicationEnvironment);

        $sockPoller = $this->getMockBuilder(SockPollerService::class)->setConstructorArgs([$taskLoggerService, $apiService])->getMock();
        $sockPoller->expects($this->once())->method('doPolling')
            ->with($task)
            ->willThrowException(new \Exception());

        $provisioner = new PollingProvisioner($dataValueService, $taskLoggerService, $apiService, $entityManager, $sockPoller);
        $provisioner->setTask($task);
        $provisioner->run();
    }

    public function testGetName()
    {
        $dataValueService = $this->getDataValueServiceMock();
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock();
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);
        $provisioner = new PollingProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );
        $this->assertEquals($provisioner->getName(), 'Polling for sock accounts, applications and databases');
    }

    protected function getProvisionerClass()
    {
        return PollingProvisioner::class;
    }
}
