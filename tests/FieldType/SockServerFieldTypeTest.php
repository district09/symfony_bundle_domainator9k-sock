<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\FieldType;

use DigipolisGent\Domainator9k\SockBundle\FieldType\SockServerFieldType;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SockServerFieldTypeTest extends TestCase
{

    public function testGetName()
    {
        $this->assertEquals('sock_server', SockServerFieldType::getName());
    }

    public function testGetFormType()
    {
        $apiService = $this->getApiServiceMock();
        $cache = $this->getCacheMock();
        $fieldType = new SockServerFieldType($apiService, $cache);
        $this->assertEquals(ChoiceType::class, $fieldType->getFormType());
    }

    public function testGetOptions()
    {
        $apiService = $this->getApiServiceMock();
        $cache = $this->getCacheMock();

        $servers = [
            [
                'id' => 1,
                'hostname' => 'example-one.dev',
            ],
            [
                'id' => 2,
                'hostname' => 'example-two.dev',
            ],
            [
                'id' => 3,
                'hostname' => 'example-three.dev',
            ],
        ];

        $apiService
            ->expects($this->atLeastOnce())
            ->method('getVirtualServers')
            ->willReturn($servers);

        $fieldType = new SockServerFieldType($apiService, $cache);
        $options = $fieldType->getOptions(1);

        $this->assertArrayHasKey('multiple',$options);
        $this->assertArrayHasKey('expanded',$options);
        $this->assertArrayHasKey('choices',$options);
        $this->assertArrayHasKey('data',$options);

        $this->assertCount(3,$options['choices']);
    }


    private function getApiServiceMock()
    {
        $mock = $this
            ->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    private function getCacheMock()
    {
        return new ArrayAdapter();
    }
}
