<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class BuildProvisionerTest extends AbstractProvisionerTest
{

    public function testOnBuildWithDatabase()
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

        $apiServiceFunctions = [
            [
                'method' => 'getEvents',
                'with' => ['accounts', 1],
                'willReturn' => []
            ],
            [
                'method' => 'getEvents',
                'with' => ['applications', 2],
                'willReturn' => []
            ],
            [
                'method' => 'getEvents',
                'with' => ['databases', 3],
                'willReturn' => []
            ]
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $arguments = [$dataValueService, $taskService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                return 1;
            },
            'createSockApplication' => function () {
                return 2;
            },
            'createSockDatabase' => function () {
                return 3;
            }
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->run($task);
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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockAccount', $applicationEnvironment, $server);
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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockAccount', $applicationEnvironment, $server);
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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createAccount');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockAccount', $applicationEnvironment, $server);
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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockApplication', $applicationEnvironment);
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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createApplication');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockApplication', $applicationEnvironment);
    }

    public function testCreateSockDatabase()
    {
        $application = new FooApplication();
        $environment = new Environment();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $applicationEnvironment->setEnvironment($environment);

        $entityManagerFunctions = [
            [
                'method' => 'persist',
                'willReturn' => null,
            ],
            [
                'method' => 'flush',
                'willReturn' => null,
            ],
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => 1,
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getAccount',
                'willReturn' => null,
            ],
            [
                'method' => 'findDatabaseByName',
                'willReturn' => null,
            ],
            [
                'method' => 'createDatabase',
                'willReturn' => [
                    'id' => 10,
                    'database_grants' => [
                        0 => [
                            'login' => 'my-login'
                        ]
                    ]
                ],
            ],
            [
                'method' => 'removeDatabaseLogin',
                'willReturn' => null,
            ],
            [
                'method' => 'addDatabaseLogin',
                'willReturn' => null,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockDatabase', $applicationEnvironment);
    }

    public function testCreateExistingSockDatabase()
    {
        $application = new FooApplication();
        $environment = new Environment();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $applicationEnvironment->setEnvironment($environment);

        $entityManagerFunctions = [
            [
                'method' => 'persist',
                'willReturn' => null,
            ],
            [
                'method' => 'flush',
                'willReturn' => null,
            ],
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => 1,
            ],
            [
                'method' => 'storeValue',
                'willReturn' => null,
            ],
        ];

        $database = [
            'id' => uniqid(),
            'database_grants' => [
                0 => [
                    'login' => 'my-login'
                ]
            ]
        ];

        $apiServiceFunctions = [
            [
                'method' => 'getAccount',
                'willReturn' => null,
            ],
            [
                'method' => 'findDatabaseByName',
                'willReturn' => $database,
            ],
            [
                'method' => 'removeDatabaseLogin',
                'willReturn' => null,
            ],
            [
                'method' => 'addDatabaseLogin',
                'willReturn' => null,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createDatabase');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildProvisioner(
            $dataValueService,
            $taskService,
            $apiService,
            $entityManager
        );

        $this->invokeProvisionerMethod($listener, 'createSockDatabase', $applicationEnvironment);
    }

    public function testOnBuildWithoutDatabase()
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
        $application->setHasDatabase(false);

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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this
            ->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiService->expects($this->exactly(2))
            ->method('getEvents')
            ->withConsecutive(['accounts', 1], ['applications', 2])
            ->willReturn([]);

        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $arguments = [$dataValueService, $taskService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                return 1;
            },
            'createSockApplication' => function () {
                return 2;
            },
            'createSockDatabase' => function () {
                return null;
            }
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->run($task);
    }

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
        $taskService = $this->getTaskServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $arguments = [$dataValueService, $taskService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                throw new ClientException('This is an exception.', $this->getRequestMock());
            },
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->run($task);
    }
}
