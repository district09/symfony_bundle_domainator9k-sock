<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provider;

use DigipolisGent\Domainator9k\SockBundle\Provider\DataTypeProvider;
use PHPUnit\Framework\TestCase;

class DataTypeProviderTest extends TestCase
{

    public function testGetDataTypes()
    {
        $provider = new DataTypeProvider();
        $dataTypes = $provider->getDataTypes();

        foreach ($dataTypes as $dataTypeArr) {
            $this->assertArrayHasKey('key', $dataTypeArr);
            $this->assertInternalType('string',$dataTypeArr['key']);
            $this->assertArrayHasKey('label', $dataTypeArr);
            $this->assertInternalType('string',$dataTypeArr['label']);
            $this->assertArrayHasKey('required', $dataTypeArr);
            $this->assertInternalType('boolean',$dataTypeArr['required']);
            $this->assertArrayHasKey('field_type', $dataTypeArr);
            $this->assertInternalType('string',$dataTypeArr['field_type']);
            $this->assertArrayHasKey('entity_types', $dataTypeArr);
            $this->assertInternalType('array',$dataTypeArr['entity_types']);
        }
    }
}
