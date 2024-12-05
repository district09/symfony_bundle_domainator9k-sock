<?php


namespace DigipolisGent\Domainator9k\SockBundle\Tests\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Environment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\Domainator9k\SockBundle\Tests\Fixtures\FooApplication;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AbstractProvisionerTest extends TestCase
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

            $method = $mock
                ->expects($this->at($index))
                ->method($method);
            if (isset($functionArr['with'])) {
                call_user_func_array([$method, 'with'], $functionArr['with']);
            }
            $method->willReturn($willReturn);

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

    protected function getProvisionerMock(array $arguments, array $methods)
    {
        $mock = $this
            ->getMockBuilder($this->getProvisionerClass())
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

    /**
     * Call a protected/private method of a provisioner.
     *
     * @param object &$object
     *   The provisioner.
     * @param string $methodName
     *   Name of the method to call.
     * @param array $args,..
     *  Arguments to pass to method.
     *
     * @return mixed
     *   Method return.
     */
    protected function invokeProvisionerMethod($provisioner, $methodName)
    {
        $environment = new Environment();
        $environment->setName('test');

        $application = new FooApplication();

        $applicationEnvironment = new ApplicationEnvironment();
        $applicationEnvironment->setEnvironment($environment);
        $applicationEnvironment->setApplication($application);

        $task = new Task();
        $task->setType($provisioner instanceof BuildProvisioner ? Task::TYPE_BUILD : Task::TYPE_DESTROY);
        $task->setStatus(Task::STATUS_NEW);
        $task->setApplicationEnvironment($applicationEnvironment);

        $reflection = new \ReflectionClass(get_class($provisioner));

        $property = $reflection->getProperty('task');
        $property->setAccessible(true);
        $property->setValue($provisioner, $task);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        $args = func_get_args();
        $args = array_splice($args, 2);

        return $method->invokeArgs($provisioner, $args);
    }

    abstract protected function getProvisionerClass();
}
