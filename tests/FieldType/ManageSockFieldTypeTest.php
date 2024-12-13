<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\SockBundle\FieldType\ManageSockFieldType;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ValidatorBuilder;

class ManageSockFieldTypeTest extends TestCase
{

    public function testGetName()
    {
        $this->assertEquals('manage_sock', ManageSockFieldType::getName());
    }

    public function testGetOptionsWithoutErrors()
    {
        $environment = new Environment();
        $environment->setName('prod');

        $serverOne = new VirtualServer();
        $serverOne->setEnvironment($environment);
        $serverOne->setName('Server one');

        $serverTwo = new VirtualServer();
        $serverTwo->setEnvironment($environment);
        $serverTwo->setName('Server two');

        $servers = new ArrayCollection();
        $servers->add($serverOne);
        $servers->add($serverTwo);

        $serverRepository = $this->getServerRepository($environment, $servers);
        $entityManager = $this->getEntityManagerMock($serverRepository);
        $dataValueService = $this->getDataValueServiceMock(false);
        $validator = $this->getValidator();

        $fieldType = new ManageSockFieldType($entityManager, $dataValueService);
        $fieldType->setOriginEntity($serverOne);
        $options = $fieldType->getOptions(false);

        $this->assertArrayHasKey('constraints', $options);

        $contstraints = $options['constraints'];

        $constraintViolationList = $validator->validate(false, $contstraints);
        $this->assertCount(0, $constraintViolationList);

        $constraintViolationList = $validator->validate(true, $contstraints);
        $this->assertCount(0, $constraintViolationList);
    }

    private function getServerRepository(Environment $environment, ArrayCollection $servers)
    {
        $mock = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->atLeastOnce())
            ->method('findBy')
            ->with($this->equalTo(['environment' => $environment]))
            ->willReturn($servers);

        return $mock;
    }

    private function getEntityManagerMock($serverRepository)
    {
        $mock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(VirtualServer::class)
            ->willReturn($serverRepository);

        return $mock;
    }

    private function getDataValueServiceMock($returnValue)
    {
        $mock = $this
            ->getMockBuilder(DataValueService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn($returnValue);

        return $mock;
    }

    /**
     * @return \Symfony\Component\Validator\Validator\RecursiveValidator
     */
    private function getValidator()
    {
        $validatorBuilder = new ValidatorBuilder();
        return $validatorBuilder->getValidator();
    }

    public function testGetOptionsWithErrors()
    {
        $environment = new Environment();
        $environment->setName('prod');

        $serverOne = new VirtualServer();
        $serverOne->setEnvironment($environment);
        $serverOne->setName('Server one');

        $serverTwo = new VirtualServer();
        $serverTwo->setEnvironment($environment);
        $serverTwo->setName('Server two');

        $servers = new ArrayCollection();
        $servers->add($serverOne);
        $servers->add($serverTwo);

        $serverRepository = $this->getServerRepository($environment, $servers);
        $entityManager = $this->getEntityManagerMock($serverRepository);
        $dataValueService = $this->getDataValueServiceMock(true);
        $validator = $this->getValidator();

        $fieldType = new ManageSockFieldType($entityManager, $dataValueService);
        $fieldType->setOriginEntity($serverOne);
        $options = $fieldType->getOptions(true);

        $constraints = $options['constraints'];

        $constraintViolationList = $validator->validate(true, $constraints);
        $this->assertCount(1, $constraintViolationList);

        $violation = $constraintViolationList->offsetGet(0);
        $expectedMessage = 'The server with name Server two is allready managing sock for the prod environment';
        $this->assertEquals($expectedMessage, $violation->getMessage());
    }

}
