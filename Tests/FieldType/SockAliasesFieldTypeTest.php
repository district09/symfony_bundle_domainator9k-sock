<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\FieldType;

use DigipolisGent\Domainator9k\SockBundle\FieldType\SockAliasesFieldType;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SockAliasesFieldTypeTest extends TestCase
{

    public function testGetName()
    {
        $this->assertEquals('sock_aliases', SockAliasesFieldType::getName());
    }

    public function testGetFormType()
    {
        $fieldType = new SockAliasesFieldType();
        $this->assertEquals(CollectionType::class, $fieldType->getFormType());
    }

    public function testGetOptionsForApplication()
    {
        $originEntity = new FooApplication();

        $value = '["example.com","example.be"]';

        $fieldType = new SockAliasesFieldType();
        $fieldType->setOriginEntity($originEntity);
        $options = $fieldType->getOptions($value);

        $this->assertEquals($options['entry_type'], TextType::class);
        $this->assertEquals($options['allow_add'], true);
        $this->assertEquals($options['allow_delete'], true);
        $this->assertEquals($options['delete_empty'], true);
        $this->assertEquals($options['data'], json_decode($value, true));
    }


    public function testEncodeValue()
    {
        $fieldType = new SockAliasesFieldType();
        $this->assertEquals('["example.com","example.be"]', $fieldType->encodeValue(["example.com", "example.be"]));
    }

    public function testDecodeValue()
    {
        $fieldType = new SockAliasesFieldType();
        $this->assertEquals(["example.com", "example.be"], $fieldType->decodeValue('["example.com","example.be"]'));
    }
}
