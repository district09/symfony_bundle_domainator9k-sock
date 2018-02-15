<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;
use DigipolisGent\Domainator9k\SockBundle\EventListener\BuildEventListener;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\ClientException;

class BuildEventListenerTest extends AbstractEventListenerTest
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

        $dataValueService = $this->getDataValueServiceMock($dataValueServiceFunctions);
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $event = new BuildEvent($task);

        $arguments = [$dataValueService, $taskLoggerService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                return null;
            },
            'createSockApplication' => function () {
                return null;
            },
            'createSockDatabase' => function () {
                return null;
            }
        ];

        $eventListener = $this->getEventListenerMock($arguments, $methods);
        $eventListener->onBuild($event);
    }


    public function testCreateSockAccountWithParentApplication()
    {
        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $server = new VirtualServer();

        $parentApplication = new FooApplication();


        $entityManagerFunctions = [
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

        $listener = new BuildEventListener(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );

        $listener->createSockAccount($applicationEnvironment, $server);
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

        $listener = new BuildEventListener(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );

        $listener->createSockAccount($applicationEnvironment, $server);
    }

    public function testCreateSockApplication()
    {
        $application = new FooApplication();
        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);

        $parentApplication = new FooApplication();

        $entityManagerFunctions = [
        ];

        $dataValueServiceFunctions = [
            [
                'method' => 'getValue',
                'willReturn' => $parentApplication,
            ],
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

        $listener = new BuildEventListener(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );

        $listener->createSockApplication($applicationEnvironment);
    }

    public function testCreateSockDatabase()
    {
        $application = new FooApplication();
        $environment = new Environment();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setApplication($application);
        $applicationEnvironment->setEnvironment($environment);

        $parentApplication = new FooApplication();

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
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock($apiServiceFunctions);
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $listener = new BuildEventListener(
            $dataValueService,
            $taskLoggerService,
            $apiService,
            $entityManager
        );

        $listener->createSockDatabase($applicationEnvironment);
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
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $event = new BuildEvent($task);

        $arguments = [$dataValueService, $taskLoggerService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                return null;
            },
            'createSockApplication' => function () {
                return null;
            }
        ];

        $eventListener = $this->getEventListenerMock($arguments, $methods);
        $eventListener->onBuild($event);
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
        $taskLoggerService = $this->getTaskLoggerServiceMock();
        $apiService = $this->getApiServiceMock();
        $entityManager = $this->getEntityManagerMock($entityManagerFunctions);

        $task = new Task();
        $task->setType(Task::TYPE_BUILD);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $event = new BuildEvent($task);

        $arguments = [$dataValueService, $taskLoggerService, $apiService, $entityManager];
        $methods = [
            'createSockAccount' => function () {
                throw new ClientException('This is an exception.', $this->getRequestMock());
            },
        ];

        $eventListener = $this->getEventListenerMock($arguments, $methods);
        $eventListener->onBuild($event);
    }
}
