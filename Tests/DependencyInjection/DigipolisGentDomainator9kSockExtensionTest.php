<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\DependencyInjection;

use DigipolisGent\Domainator9k\SockBundle\DependencyInjection\DigipolisGentDomainator9kSockExtension;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DigipolisGentDomainator9kSockExtensionTest extends TestCase
{

    /**
     * @expectedException \PHPUnit\Framework\Error\Notice
     */
    public function testLoadWithoutConfig()
    {
        $apiServiceDefinition = $this->getApiServiceDefinition();
        $containerBuilder = $this->getContainerBuilderMock($apiServiceDefinition);

        $configs = [];

        $extension = new DigipolisGentDomainator9kSockExtension();
        $extension->load($configs, $containerBuilder);
    }

    public function testLoad()
    {
        $apiServiceDefinition = $this->getApiServiceDefinition();
        $containerBuilder = $this->getContainerBuilderMock($apiServiceDefinition);

        $configs = [
            'digipolis_gent_domainator9k_sock' =>
            [
                'host' => 'my-host',
                'user_token' => 'my-user-token',
                'client_token' => 'my-client-token',
            ]
        ];

        $extension = new DigipolisGentDomainator9kSockExtension();
        $extension->load($configs, $containerBuilder);
    }

    private function getApiServiceDefinition()
    {
        return new Definition(ApiService::class);
    }

    private function getContainerBuilderMock($apiServiceDefinition)
    {
        $mock = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(ApiService::class))
            ->willReturn($apiServiceDefinition);

        return $mock;
    }
}
