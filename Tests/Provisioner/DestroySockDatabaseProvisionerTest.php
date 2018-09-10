<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\DestroySockDatabaseProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class DestroySockDatabaseProvisionerTest extends AbstractProvisionerTest
{

    public function testOnDestroy()
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

        $entityManagerFunctions = [
            [
                'method' => 'getRepository',
                'willReturn' => $this->getRepositoryMock('findAll', $servers)
            ],
            [
                'method' => 'persist',
                'willReturn' => null
            ],
            [
                'method' => 'flush',
                'willReturn' => null
            ]
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => null
            ],
            [
                'method' => 'getValue',
                'willReturn' => true
            ],
            [
                'method' => 'getValue',
                'willReturn' => 5
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'removeDatabase',
                'willReturn' => null
            ]
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_DESTROY);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $provisioner = new DestroySockDatabaseProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );
        $provisioner->setTask($task);
        $provisioner->run();
    }

    /**
     * @expectedException \DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException
     */
    public function testOnDestroyWithException()
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

        $entityManagerFunctions = [
            [
                'method' => 'getRepository',
                'willReturn' => $this->getRepositoryMock('findAll', $servers)
            ],
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => false
            ],
            [
                'method' => 'getValue',
                'willReturn' => true
            ],
            [
                'method' => 'getValue',
                'willReturn' => 5
            ],
        ];

        $apiServiceFunctions = [];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $apiService
            ->expects($this->at(0))
            ->method('removeDatabase')
            ->willReturnCallback(function () {
                throw new ClientException('This is an exception.', $this->getRequestMock());
            });

        $task = new Task();
        $task->setType(Task::TYPE_DESTROY);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $provisioner = new DestroySockDatabaseProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );
        $provisioner->setTask($task);
        $provisioner->run();
    }

    public function testGetName()
    {
        $dataValueService = $this->getDataValueServiceMock();
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock();
        $provisioner = new DestroySockDatabaseProvisioner($dataValueService, $taskLoggerService, $apiService, $entityManager);
        $this->assertEquals($provisioner->getName(), 'Sock database');
    }

    protected function getProvisionerClass()
    {
      DestroySockDatabaseProvisioner::class;
    }

}
