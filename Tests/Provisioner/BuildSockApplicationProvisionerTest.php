<?php

namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockApplicationProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class BuildSockApplicationProvisionerTest extends AbstractProvisionerTest
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

        $entityManagerFunctions = [
            [
                'method' => 'getRepository',
                'willReturn' => $this->getRepositoryMock('findAll', $servers)
            ]
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => false
            ],
            [
                'method' => 'getValue',
                'willReturn' => true
            ]
        ];

        $apiServiceFunctions = [];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $arguments = [
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        ];
        $methods = [
            'createSockApplication' => function () {
                return 2;
            },
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->setTask($task);
        $provisioner->run();
    }

    public function testCreateSockApplication()
    {
        $application = new FooApplication();
        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);

        $entityManagerFunctions = [
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => 4,
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getAccount',
                'willReturn' => null,
            ],
            [
                'method' => 'findApplicationByName',
                'willReturn' => null,
            ],
            [
                'method' => 'createApplication',
                'willReturn' => [
                    'id' => 10
                ],
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockApplicationProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockApplication', $applicationEnvironment);
    }

    public function testCreateExistingSockApplication()
    {
        $application = new FooApplication();
        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);

        $entityManagerFunctions = [
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => 4,
            ],
        ];

        $application = ['id' => uniqid()];

        $apiServiceFunctions = [
            [
                'method' => 'getAccount',
                'willReturn' => null,
            ],
            [
                'method' => 'findApplicationByName',
                'willReturn' => $application,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createApplication');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockApplicationProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockApplication', $applicationEnvironment);
    }

    /**
     * @expectedException \DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException
     */
    public function testOnBuildWithException()
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
            ]
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => false
            ],
            [
                'method' => 'getValue',
                'willReturn' => true
            ]
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $arguments = [
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller,
        ];
        $methods = [
            'createSockApplication' => function () {
                throw new ClientException('This is an exception.', $this->getRequestMock());
            },
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
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
        $provisioner = new BuildSockApplicationProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );
        $this->assertEquals($provisioner->getName(), 'Sock application');
    }

    protected function getProvisionerClass()
    {
        return BuildSockApplicationProvisioner::class;
    }
}
