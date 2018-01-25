<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\EventListener\BuildEventListener;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AbstractEventListenerTest extends TestCase
{

    protected function getRepositoryMock($method, $returnValue)
    {
        $mock = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->at(0))
            ->method($method)
            ->willReturn($returnValue);

        return $mock;
    }

    protected function getDataValueServiceMock(array $functions = array())
    {
        $mock = $this
            ->getMockBuilder(DataValueService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $index = 0;
        foreach ($functions as $functionArr) {
            $method = $functionArr['method'];
            $willReturn = $functionArr['willReturn'];

            $mock
                ->expects($this->at($index))
                ->method($method)
                ->willReturn($willReturn);

            $index++;
        }

        return $mock;
    }

    protected function getTaskLoggerServiceMock()
    {
        $mock = $this
            ->getMockBuilder(TaskLoggerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }

    protected function getApiServiceMock(array $functions = array())
    {
        $mock = $this
            ->getMockBuilder(ApiService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $index = 0;
        foreach ($functions as $functionArr) {
            $method = $functionArr['method'];
            $willReturn = $functionArr['willReturn'];

            $mock
                ->expects($this->at($index))
                ->method($method)
                ->willReturn($willReturn);

            $index++;
        }

        return $mock;
    }

    protected function getEntityManagerMock(array $functions = array())
    {
        $mock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $index = 0;
        foreach ($functions as $functionArr) {
            $method = $functionArr['method'];
            $willReturn = $functionArr['willReturn'];

            $mock
                ->expects($this->at($index))
                ->method($method)
                ->willReturn($willReturn);

            $index++;
        }


        return $mock;
    }

    protected function getEventListenerMock(array $arguments, array $methods)
    {
        $mock = $this
            ->getMockBuilder(BuildEventListener::class)
            ->setMethods(array_keys($methods))
            ->setConstructorArgs($arguments)
            ->getMock();

        foreach ($methods as $method => $callback) {
            $mock->expects($this->once())
                ->method($method)
                ->willReturnCallback($callback);
        }

        return $mock;
    }

    protected function getRequestMock()
    {
        $mock = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }
}
