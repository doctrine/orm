<?php

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\DoctrineTestCase;

class EntityManagerDecoratorTest extends DoctrineTestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrapped;

    /**
     * @var EntityManagerDecorator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decorator;

    public function setUp()
    {
        $this->wrapped = $this->createMock(EntityManagerInterface::class);
        $this->decorator = new class($this->wrapped) extends EntityManagerDecorator {};
    }

    public function getMethodParameters()
    {
        $class = new \ReflectionClass(EntityManagerInterface::class);
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->isConstructor() || $method->isStatic() || !$method->isPublic()) {
                continue;
            }

            $methods[$method->getName()] = $this->getParameters($method);
        }

        return $methods;
    }

    private function getParameters(\ReflectionMethod $method)
    {
        /** Special case EntityManager::createNativeQuery() */
        if ($method->getName() === 'createNativeQuery') {
            return [$method->getName(), ['name', new ResultSetMapping()]];
        }

        /** Special case EntityManager::transactional() */
        if ($method->getName() === 'transactional') {
            return [$method->getName(), [function () {}]];
        }

        if ($method->getNumberOfRequiredParameters() === 0) {
            return [$method->getName(), []];
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            return [$method->getName(), array_fill(0, $method->getNumberOfRequiredParameters(), 'req') ?: []];
        }

        if ($method->getNumberOfParameters() !== $method->getNumberOfRequiredParameters()) {
            return [$method->getName(), array_fill(0, $method->getNumberOfParameters(), 'all') ?: []];
        }

        return [];
    }

    /**
     * @dataProvider getMethodParameters
     */
    public function testAllMethodCallsAreDelegatedToTheWrappedInstance($method, array $parameters)
    {
        $stub = $this->wrapped
            ->expects(self::once())
            ->method($method)
        ;

        call_user_func_array([$stub, 'with'], $parameters);
        call_user_func_array([$this->decorator, $method], $parameters);
    }
}
