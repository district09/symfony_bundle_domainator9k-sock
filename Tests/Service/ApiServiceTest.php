<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Service;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ApiServiceTest extends TestCase
{

    /**
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function testRequestException()
    {
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

        $apiService = $this->getApiServiceMock($result);
        $apiService->createAccount('application-name', 68);
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
            "aliases" => [
                "www.example.com"
            ],
            "documentroot_suffix" => "public_html",
            "id" => 13,
            "name" => "exampleapp"
        ];

        $apiService = $this->getApiServiceMock($application);
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

        $apiService = $this->getApiServiceMock($result);
        $apiService->createDatabase(68, 'testclientsharedruby', 'username', 'password');
    }

    public function testRemoveDatabaseLogin()
    {
        $apiService = $this->getApiServiceMock([]);
        $apiService->removeDatabaseLogin(68, 'testclientsharedruby');
    }

    public function testAddDatabaseLogin()
    {
        $result = [
            "id" => 68,
            "name" => "testclientsharedruby",
            'account_id'
        ];

        $apiService = $this->getApiServiceMock($result);
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
        $apiService = $this->getApiServiceMock([]);
        $apiService->removeAccount(1);
    }

    public function testRemoveApplication()
    {
        $apiService = $this->getApiServiceMock([]);
        $apiService->removeApplication(68);
    }

    public function testRemoveDatabase()
    {
        $apiService = $this->getApiServiceMock([]);
        $apiService->removeDatabase(5);
    }

    private function getApiServiceMock($result)
    {
        $service = new ApiService();
        $service->setHost('example.com');
        $service->setClientToken('client-token');
        $service->setUserToken('user-token');

        $reflectionObject = new \ReflectionObject($service);
        $property = $reflectionObject->getProperty('client');
        $property->setAccessible(true);
        $property->setValue(
            null,
            $this->getClientMock($result)
        );

        return $service;
    }

    private function getClientMock($result)
    {
        $mock = $this
            ->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $body = $this->getBodyMock($result);
        $reponse = $this->getResponseMock($body);

        $mock
            ->expects($this->at(0))
            ->method('__call')
            ->willReturn($reponse);

        return $mock;
    }

    private function getBodyMock($result)
    {
        $mock = $this
            ->getMockBuilder(StreamInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->at(0))
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
            ->expects($this->at(0))
            ->method('getBody')
            ->willReturn($body);

        return $mock;
    }
}
