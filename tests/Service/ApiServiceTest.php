<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Service;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ApiServiceTest extends TestCase
{

    protected $apiHost = 'example.com';
    protected $apiClientToken = 'client-token';
    protected $apiUserToken = 'user-token';

    public function testRequestException()
    {
        $this->expectException(RequestException::class);
        $service = new ApiService();
        $service->getAccount('random');
    }

    public function testFindAccountByName()
    {
        $accounts = [
            [
                'name' => 'Account 1',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 2',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 3',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 4',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 5',
                'virtual_server_id' => null
            ]
        ];

        $apiService = $this->getApiServiceMock($accounts);

        $result = $apiService->findAccountByName('Account 1');
        $expected = [
            'name' => 'Account 1',
            'virtual_server_id' => null
        ];

        $this->assertEquals($expected, $result);
    }

    public function testFindNonExistingAccountByName()
    {
        $accounts = [
            [
                'name' => 'Account 1',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 2',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 3',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 4',
                'virtual_server_id' => null
            ],
            [
                'name' => 'Account 5',
                'virtual_server_id' => null
            ]
        ];

        $apiService = $this->getApiServiceMock($accounts);

        $result = $apiService->findAccountByName('Non existing account');
        $this->assertFalse($result);
    }

    public function testCreateAccount()
    {
        $result = [
            "ftp_users" => [],
            "id" => 68,
            "name" => "testclientsharedruby",
            "ssh_keys" => [],
            "virtual_server_id" => 3,
        ];

        $data = [
            'name' => 'account-name',
            'virtual_server_id' => 68,
            'ssh_key_ids' => []
        ];

        $apiService = $this->getApiServiceMock($result, 'post', ['/accounts', $data]);
        $apiService->createAccount('account-name', 68);
    }

    public function testGetVirtualServer()
    {
        $result = [
            "accounts" => [],
            "created_at" => "2013-03-13T16:23:56+01:00",
            "hostname" => "pro-008.sandbox",
            "id" => 4,
            "ip" => "10.10.10.105",
        ];

        $apiService = $this->getApiServiceMock($result);
        $apiService->getVirtualServer(4);
    }

    public function testGetVirtualServers()
    {
        $result = [
            [
                "accounts" => [],
                "created_at" => "2013-03-13T16:23:56+01:00",
                "hostname" => "pro-008.sandbox",
                "id" => 4,
                "ip" => "10.10.10.105",
            ],
        ];

        $apiService = $this->getApiServiceMock($result);
        $apiService->getVirtualServers();
    }

    public function testFindApplicationByName()
    {
        $applications = [
            [
                'name' => 'Application 1',
                'account_id' => 1
            ],
            [
                'name' => 'Application 2',
                'account_id' => 1
            ],
            [
                'name' => 'Application 3',
                'account_id' => 1
            ],
            [
                'name' => 'Application 4',
                'account_id' => 1
            ],
            [
                'name' => 'Application 5',
                'account_id' => 1
            ]
        ];

        $apiService = $this->getApiServiceMock($applications);
        $result = $apiService->findApplicationByName('Application 1', 1);
        $expected = [
            'name' => 'Application 1',
            'account_id' => 1
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFindApplicationByNonExistingName()
    {
        $applications = [];

        $apiService = $this->getApiServiceMock($applications);
        $result = $apiService->findApplicationByName('Application 1');
        $this->assertFalse($result);
    }

    public function testCreateApplication()
    {
        $application = [
            "account_id" => 7,
            "created_at" => "2013-03-14T14:03:16+01:00",
            "aliases" => [],
            "documentroot_suffix" => "current",
            "id" => 13,
            "name" => "exampleapp"
        ];

        $data = [
            'account_id' => 7,
            'name' => 'exampleapp',
            'aliases' => [],
            'documentroot_suffix' => 'current',
            'technology' => 'php-fpm',
        ];
        $apiService = $this->getApiServiceMock($application, 'post', ['/applications', $data]);
        $apiService->createApplication(7, 'exampleapp');
    }

    public function testFindDatabaseByName()
    {
        $databases = [
            [
                'name' => 'Database 1',
                'account_id' => 1
            ],
            [
                'name' => 'Database 2',
                'account_id' => 1
            ],
            [
                'name' => 'Database 3',
                'account_id' => 1
            ],
            [
                'name' => 'Database 4',
                'account_id' => 1
            ],
            [
                'name' => 'Database 5',
                'account_id' => 1
            ]
        ];

        $apiService = $this->getApiServiceMock($databases);
        $result = $apiService->findDatabaseByName('Database 1', 1);
        $expected = [
            'name' => 'Database 1',
            'account_id' => 1
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFindDatabaseByNonExistingName()
    {
        $databases = [];

        $apiService = $this->getApiServiceMock($databases);
        $result = $apiService->findDatabaseByName('Database 1', 1);
        $this->assertFalse($result);
    }

    public function testCreateDatabase()
    {
        $result = [
            "id" => 68,
            "name" => "testclientsharedruby",
            'account_id'
        ];

        $data = [
            'account_id' => 68,
            'name' => 'testclientsharedruby',
            'login' => 'username',
            'password' => 'password',
        ];

        $apiService = $this->getApiServiceMock($result, 'post', ['/databases', $data]);
        $apiService->createDatabase(68, 'testclientsharedruby', 'username', 'password');
    }

    public function testUpdateDatabase()
    {
        $result = '';
        $databaseId = uniqid();
        $data = ['login' => 'my-login', 'password' => 'my-new-pw'];
        $apiService = $this->getApiServiceMock($result, 'patch', ['/databases/' . $databaseId . '/update_login', $data]);
        $apiService->updateDatabaseLogin($databaseId, 'my-login', 'my-new-pw');
    }

    public function testRemoveDatabaseLogin()
    {
        $apiService = $this->getApiServiceMock([], 'delete', ['/databases/68/remove_login', ['login' => 'testclientsharedruby']]);
        $apiService->removeDatabaseLogin(68, 'testclientsharedruby');
    }

    public function testAddDatabaseLogin()
    {
        $result = [
            "id" => 68,
            "name" => "testclientsharedruby",
            'account_id'
        ];

        $data = ['login' => 'user', 'password' => 'password'];

        $apiService = $this->getApiServiceMock($result, 'post', ['/databases/68/add_login', $data]);
        $apiService->addDatabaseLogin(68, 'user', 'password');
    }

    public function testGetSshKeys()
    {
        $result = [
            "description" => "jorendegroof@dhcp122.om",
            "favorite" => true,
            "id" => 1,
            "key" => "ssh-rsa AAAAB3Nz....1RDgxQ=="
        ];

        $apiService = $this->getApiServiceMock($result);
        $apiService->getSshKeys();
    }

    public function testRemoveAccount()
    {
        $apiService = $this->getApiServiceMock([], 'delete', ['/accounts/1']);
        $apiService->removeAccount(1);
    }

    public function testRemoveApplication()
    {
        $apiService = $this->getApiServiceMock([], 'delete', ['/applications/68']);
        $apiService->removeApplication(68);
    }

    public function testRemoveDatabase()
    {
        $apiService = $this->getApiServiceMock([], 'delete', ['/databases/5']);
        $apiService->removeDatabase(5);
    }

    public function testAddApplicationAlias()
    {
        $result = true;
        $applicationId = uniqid();
        $alias = uniqid() . '.com';
        $apiService = $this->getApiServiceMock(
            $result,
            'post',
            [
                '/applications/' . $applicationId . '/add_alias',
                [
                    'alias' => $alias
                ]
            ]
        );
        $this->assertTrue($apiService->addApplicationAlias($applicationId, $alias));
    }

    public function testRemoveApplicationAlias()
    {
        $result = true;
        $applicationId = uniqid();
        $alias = uniqid() . '.com';
        $apiService = $this->getApiServiceMock(
            $result,
            'delete',
            [
                '/applications/' . $applicationId . '/remove_alias',
                [
                    'alias' => $alias
                ]
            ]
        );
        $this->assertTrue($apiService->removeApplicationAlias($applicationId, $alias));
    }

    private function getApiServiceMock($result, $method = 'get', $parameters = array())
    {
        $service = new ApiService();
        $service->setHost($this->apiHost);
        $service->setClientToken($this->apiClientToken);
        $service->setUserToken($this->apiUserToken);

        $reflectionObject = new \ReflectionObject($service);
        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);
        $property->setValue(
            null,
            $this->getClientMock($result, $method, $parameters)
        );

        return $service;
    }

    private function getClientMock($result, $method = 'get', $parameters = array())
    {
        $mock = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $body = $this->getBodyMock($result);
        $response = $this->getResponseMock($body);

        $methodMock = $mock
            ->expects($this->atLeastOnce())
            ->method($method);
        if ($method) {
            switch ($method) {
                case 'post':
                case 'delete':
                case 'patch':
                    $key = $method !== 'delete' ? \GuzzleHttp\RequestOptions::JSON : 'form_params';
                    $parameters = [
                        $this->apiHost . $parameters[0],
                        [
                            $key => array_merge(
                                $parameters[1] ?? [],
                                [
                                    'client_token' => $this->apiClientToken,
                                    'user_token' => $this->apiUserToken,
                                ]
                            ),
                            'headers' => [
                                'Accept' => 'application/json',
                            ],
                            'query' => [],
                        ],
                    ];
                    break;
                case 'get':
                    if (count($parameters) < 2) {
                      break;
                    }
                    $parameters = [
                        $this->apiHost . $parameters[0],
                        [
                        'form_params' => [
                                'client_token' => $this->clientToken,
                                'user_token' => $this->userToken,
                        ],
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                        'query' => $parameters[1],
                        ],
                    ];
                    break;
            }
            if ($parameters) {
                $methodMock->with(...$parameters);
            }
        }
        $methodMock->willReturn($response);

        return $mock;
    }

    private function getBodyMock($result)
    {
        $mock = $this
            ->getMockBuilder(StreamInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->atLeastOnce())
            ->method('getContents')
            ->willReturn(json_encode($result));

        return $mock;
    }

    private function getResponseMock($body)
    {
        $mock = $this
            ->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn($body);

        return $mock;
    }
}
