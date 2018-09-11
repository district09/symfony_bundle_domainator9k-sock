<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockAccountProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class BuildSockAccountProvisionerTest extends AbstractProvisionerTest
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

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setProvisioners([BuildSockAccountProvisioner::class]);
        $task->setApplicationEnvironment($applicationEnvironment);

        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $arguments = [
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        ];
        $methods = [
            'createSockAccount' => function () {
                return 1;
            },
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->setTask($task);
        $provisioner->run();
    }

    public function testCreateSockAccountWithParentApplication()
    {
        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $server = new VirtualServer();

        $parentApplication = new FooApplication();


        $entityManagerFunctions = [
            [
                'method' => 'getRepository',
                'willReturn' => $this->getRepositoryMock('findOneBy', new FooApplication()),
            ]
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => $parentApplication
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1
            ],
            [
                'method' => 'getValue',
                'willReturn' => 'username'
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getVirtualServer',
                'willReturn' => null
            ]
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockAccountProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockAccount', $applicationEnvironment, $server);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The parent application must be build first.
     */
    public function testCreateSockAccountWithParentApplicationNotBuilt()
    {
        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $server = new VirtualServer();

        $parentApplication = new FooApplication();


        $entityManagerFunctions = [
            [
                'method' => 'getRepository',
                'willReturn' => $this->getRepositoryMock('findOneBy', new FooApplication()),
            ]
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => $parentApplication
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1
            ],
            [
                'method' => 'getValue',
                'willReturn' => null
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getVirtualServer',
                'willReturn' => null
            ]
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockAccountProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockAccount', $applicationEnvironment, $server);
    }

    public function testCreateSockAccountWithoutParentApplication()
    {
        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $server = new VirtualServer();


        $entityManagerFunctions = [
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => false,
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1,
            ],
            [
                'method' => 'getValue',
                'willReturn' => [
                    1,
                    2,
                    3,
                    4,
                    5,
                ]
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getVirtualServer',
                'willReturn' => null,
            ],
            [
                'method' => 'findAccountByName',
                'willReturn' => null,
            ],
            [
                'method' => 'createAccount',
                'willReturn' => null,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockAccountProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockAccount', $applicationEnvironment, $server);
    }

    public function testCreateExistingSockAccount()
    {
        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $server = new VirtualServer();


        $entityManagerFunctions = [
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => false,
            ],
            [
                'method' => 'getValue',
                'willReturn' => 1,
            ],
            [
                'method' => 'getValue',
                'willReturn' => [
                    1,
                    2,
                    3,
                    4,
                    5,
                ]
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
        ];

        $account = ['id' => uniqid()];

        $apiServiceFunctions = [
            [
                'method' => 'getVirtualServer',
                'willReturn' => null,
            ],
            [
                'method' => 'findAccountByName',
                'willReturn' => $account,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createAccount');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockAccountProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockAccount', $applicationEnvironment, $server);
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
            $sockPoller
        ];
        $methods = [
            'createSockAccount' => function () {
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
        $provisioner = new BuildSockAccountProvisioner($dataValueService, $taskLoggerService, $apiService, $entityManager, $sockPoller);
        $this->assertEquals($provisioner->getName(), 'Sock account');
    }

    protected function getProvisionerClass()
    {
        return BuildSockAccountProvisioner::class;
    }
}
