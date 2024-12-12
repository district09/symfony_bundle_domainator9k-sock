<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\SockBundle\FieldType\SshKeyChoiceFieldType;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SshKeyChoiceFieldTypeTest extends TestCase
{

    public function testGetName()
    {
        $this->assertEquals('ssh_key_choice', SshKeyChoiceFieldType::getName());
    }

    public function testGetFormType()
    {
        $apiService = $this->getApiServiceMock();
        $dataValueService = $this->getDataValueServiceMock();
        $fieldType = new SshKeyChoiceFieldType($apiService, $dataValueService);
        $this->assertEquals(ChoiceType::class, $fieldType->getFormType());
    }

    public function testGetOptionsForApplication()
    {
        $apiService = $this->getApiServiceMock();
        $dataValueService = $this->getDataValueServiceMock();

        $originEntity = new FooApplication();

        $value = '[1,2]';

        $sshKeys = [];

        for ($i = 0; $i < 10; $i++) {
            $sshKeys[] = [
                'id' => $i,
                'description' => 'Key ' . $i
            ];
        }

        $apiService
            ->expects($this->atLeastOnce())
            ->method('getSshKeys')
            ->willReturn($sshKeys);

        $fieldType = new SshKeyChoiceFieldType($apiService, $dataValueService);
        $fieldType->setOriginEntity($originEntity);
        $options = $fieldType->getOptions($value);

        $this->assertArrayHasKey('multiple', $options);
        $this->assertArrayHasKey('expanded', $options);
        $this->assertArrayHasKey('choices', $options);
        $this->assertArrayHasKey('data', $options);

        $this->assertCount(2, $options['data']);
        $this->assertCount(10, $options['choices']);
    }


    public function testGetOptionsForApplicationEnvironment()
    {
        $apiService = $this->getApiServiceMock();
        $dataValueService = $this->getDataValueServiceMock();

        $originEntity = new ApplicationEnvironment();

        $value = '[1,2]';

        $sshKeys = [];

        for ($i = 0; $i < 10; $i++) {
            $sshKeys[] = [
                'id' => $i,
                'description' => 'Key ' . $i
            ];
        }

        $apiService
            ->expects($this->atLeastOnce())
            ->method('getSshKeys')
            ->willReturn($sshKeys);

        $dataValueService
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(['1','5','6','8']);

        $fieldType = new SshKeyChoiceFieldType($apiService, $dataValueService);
        $fieldType->setOriginEntity($originEntity);
        $options = $fieldType->getOptions($value);

        $this->assertArrayHasKey('multiple', $options);
        $this->assertArrayHasKey('expanded', $options);
        $this->assertArrayHasKey('choices', $options);
        $this->assertArrayHasKey('data', $options);

        $this->assertCount(4, $options['data']);
        $this->assertCount(10, $options['choices']);
    }


    public function testEncodeValue()
    {
        $apiService = $this->getApiServiceMock();
        $dataValueService = $this->getDataValueServiceMock();
        $fieldType = new SshKeyChoiceFieldType($apiService, $dataValueService);
        $this->assertEquals('["two"]', $fieldType->encodeValue(['one' => 'two']));
    }

    public function testDecodeValue()
    {
        $apiService = $this->getApiServiceMock();
        $dataValueService = $this->getDataValueServiceMock();
        $fieldType = new SshKeyChoiceFieldType($apiService, $dataValueService);
        $this->assertEquals(['one' => 'two'], $fieldType->decodeValue('{"one":"two"}'));
    }

    private function getApiServiceMock()
    {
        $mock = $this
            ->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    private function getDataValueServiceMock()
    {
        $mock = $this
            ->getMockBuilder(DataValueService::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }
}
