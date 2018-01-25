<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\AbstractApplication;
use DigipolisGent\Domainator9k\SockBundle\FieldType\ParentApplicationFieldType;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ParentApplicationFieldTypeTest extends TestCase
{

    public function testGetFormType()
    {
        $entityManager = $this->getEntityManagerMock();
        $fieldType = new ParentApplicationFieldType($entityManager);
        $this->assertEquals(ChoiceType::class, $fieldType->getFormType());
    }

    private function getEntityManagerMock()
    {
        $mock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    public function testGetName()
    {
        $this->assertEquals('parent_application', ParentApplicationFieldType::getName());
    }

    public function testEncodeValue()
    {
        $repository = $this->getApplicationRepositoryMock();
        $repository
            ->expects($this->at(0))
            ->method('find')
            ->willReturn(new FooApplication());

        $entityManager = $this->getEntityManagerMock();
        $entityManager
            ->expects($this->at(0))
            ->method('getRepository')
            ->with(AbstractApplication::class)
            ->willReturn($repository);

        $fieldType = new ParentApplicationFieldType($entityManager);
        $result = $fieldType->decodeValue('the-application-uuid');
        $this->assertInstanceOf(FooApplication::class, $result);
    }

    private function getApplicationRepositoryMock()
    {
        $mock = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    public function testGetOptions()
    {
        $applications = new ArrayCollection();

        $applicationOne = new FooApplication();
        $applicationOne->setName('Foo one');
        $applicationOne->setId('foo-id-one');
        $applications->add($applicationOne);

        $applicationTwo = new FooApplication();
        $applicationTwo->setName('Foo two');
        $applicationTwo->setId('foo-id-two');
        $applications->add($applicationTwo);

        $applicationThree = new FooApplication();
        $applicationThree->setName('Foo three');
        $applicationThree->setId('foo-id-three');
        $applications->add($applicationThree);

        $repository = $this->getApplicationRepositoryMock();
        $repository
            ->expects($this->at(0))
            ->method('findAll')
            ->willReturn($applications);

        $entityManager = $this->getEntityManagerMock();
        $entityManager
            ->expects($this->at(0))
            ->method('getRepository')
            ->with(AbstractApplication::class)
            ->willReturn($repository);

        $fieldType = new ParentApplicationFieldType($entityManager);
        $fieldType->setOriginEntity($applicationOne);
        $options = $fieldType->getOptions($applicationTwo->getId());

        $this->assertArrayHasKey('multiple', $options);
        $this->assertArrayHasKey('expanded', $options);
        $this->assertArrayHasKey('choices', $options);
        $this->assertArrayHasKey('data', $options);

        $this->assertEquals($applicationTwo->getId(), $options['data']);
        $this->assertCount(2,$options['choices']);
    }
}
