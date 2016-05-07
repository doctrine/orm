<?php

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class EntityManagerDecoratorTest extends \PHPUnit_Framework_TestCase
{
    private $wrapped;
    private $decorator;

    public function setUp()
    {
        $this->wrapped = $this->createMock(EntityManagerInterface::class);
        $this->decorator = $this->getMockBuilder(EntityManagerDecorator::class)
            ->setConstructorArgs([$this->wrapped])
            ->setMethods(null)
            ->getMock();
    }

    public function getMethodParameters()
    {
        $class = new \ReflectionClass(EntityManager::class);

        $methods = [];
        foreach ($class->getMethods() as $method) {
            if ($method->isConstructor() || $method->isStatic() || !$method->isPublic()) {
                continue;
            }

            /** Special case EntityManager::createNativeQuery() */
            if ($method->getName() === 'createNativeQuery') {
                $methods[] = [$method->getName(), ['name', new ResultSetMapping()]];
                continue;
            }

            if ($method->getNumberOfRequiredParameters() === 0) {
                $methods[] = [$method->getName(), []];
            } elseif ($method->getNumberOfRequiredParameters() > 0) {
                $methods[] = [$method->getName(), array_fill(0, $method->getNumberOfRequiredParameters(), 'req') ?: []];
            }
            if ($method->getNumberOfParameters() != $method->getNumberOfRequiredParameters()) {
                $methods[] = [$method->getName(), array_fill(0, $method->getNumberOfParameters(), 'all') ?: []];
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

        call_user_func_array([$stub, 'with'], $parameters);

        self::assertSame('INNER VALUE FROM ' . $method, call_user_func_array([$this->decorator, $method], $parameters));
    }
}
