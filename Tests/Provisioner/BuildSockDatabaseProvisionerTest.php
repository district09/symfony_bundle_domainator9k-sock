<?php

namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockDatabaseProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Service\SockPollerService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class BuildSockDatabaseProvisionerTest extends AbstractProvisionerTest
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
            $sockPoller,
        ];
        $methods = [
            'createSockDatabase' => function () {
                return 3;
            }
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->setTask($task);
        $provisioner->run();
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
                'method' => 'addDatabaseLogin',
                'willReturn' => null,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockDatabaseProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockDatabase', $applicationEnvironment);
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
                'method' => 'addDatabaseLogin',
                'willReturn' => null,
            ],
        ];

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $apiService->expects($this->never())->method('createDatabase');
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);
        $sockPoller = new SockPollerService($taskLoggerService, $apiService);

        $provisioner = new BuildSockDatabaseProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );

        $this->invokeProvisionerMethod($provisioner, 'createSockDatabase', $applicationEnvironment);
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
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this
            ->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'createSockDatabase' => function () {
                return null;
            }
        ];

        $provisioner = $this->getProvisionerMock($arguments, $methods);
        $provisioner->setTask($task);
        $provisioner->run();
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
            'createSockDatabase' => function () {
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
        $provisioner = new BuildSockDatabaseProvisioner(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager,
            $sockPoller
        );
        $this->assertEquals($provisioner->getName(), 'Sock database');
    }

    protected function getProvisionerClass()
    {
        return BuildSockDatabaseProvisioner::class;
    }
}
