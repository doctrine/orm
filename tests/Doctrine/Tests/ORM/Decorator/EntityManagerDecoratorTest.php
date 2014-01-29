<?php

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class EntityManagerDecoratorTest extends \PHPUnit_Framework_TestCase
{
    private $wrapped;
    private $decorator;

    public function setUp()
    {
        $this->wrapped = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->decorator = $this->getMockBuilder('Doctrine\ORM\Decorator\EntityManagerDecorator')
            ->setConstructorArgs(array($this->wrapped))
            ->setMethods(null)
            ->getMock();
    }

    public function getMethodParameters()
    {
        $class = new \ReflectionClass('Doctrine\ORM\EntityManager');

        $methods = array();
        foreach ($class->getMethods() as $method) {
            if ($method->isConstructor() || $method->isStatic() || !$method->isPublic()) {
                continue;
            }

            /** Special case EntityManager::createNativeQuery() */
            if ($method->getName() === 'createNativeQuery') {
                $methods[] = array($method->getName(), array('name', new ResultSetMapping()));
                continue;
            }

            if ($method->getNumberOfRequiredParameters() === 0) {
                $methods[] = array($method->getName(), array());
            } elseif ($method->getNumberOfRequiredParameters() > 0) {
                $methods[] = array($method->getName(), array_fill(0, $method->getNumberOfRequiredParameters(), 'req') ?: array());
            }
            if ($method->getNumberOfParameters() != $method->getNumberOfRequiredParameters()) {
                $methods[] = array($method->getName(), array_fill(0, $method->getNumberOfParameters(), 'all') ?: array());
            }
        }

        return $methods;
    }

    /**
     * @dataProvider getMethodParameters
     */
    public function testAllMethodCallsAreDelegatedToTheWrappedInstance($method, array $parameters)
    {
        $stub = $this->wrapped
            ->expects($this->once())
            ->method($method)
            ->will($this->returnValue('INNER VALUE FROM ' . $method));

        call_user_func_array(array($stub, 'with'), $parameters);

        $this->assertSame('INNER VALUE FROM ' . $method, call_user_func_array(array($this->decorator, $method), $parameters));
    }
}
