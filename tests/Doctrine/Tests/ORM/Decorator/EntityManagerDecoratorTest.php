<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\DoctrineTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;
use function array_fill;
use function call_user_func_array;

class EntityManagerDecoratorTest extends DoctrineTestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $wrapped;

    /** @var EntityManagerDecorator|\PHPUnit\Framework\MockObject\MockObject */
    private $decorator;

    public function setUp() : void
    {
        $this->wrapped   = $this->createMock(EntityManagerInterface::class);
        $this->decorator = new class($this->wrapped) extends EntityManagerDecorator {
        };
    }

    public function getMethodParameters()
    {
        $class   = new ReflectionClass(EntityManagerInterface::class);
        $methods = [];

        foreach ($class->getMethods() as $method) {
            if ($method->isConstructor() || $method->isStatic() || ! $method->isPublic()) {
                continue;
            }

            $methods[$method->getName()] = $this->getParameters($method);
        }

        return $methods;
    }

    private function getParameters(ReflectionMethod $method)
    {
        /** Special case EntityManager::createNativeQuery() */
        if ($method->getName() === 'createNativeQuery') {
            return [$method->getName(), ['name', new ResultSetMapping()]];
        }

        /** Special case EntityManager::transactional() */
        if ($method->getName() === 'transactional') {
            return [
                $method->getName(),
                [
                    static function () {
                    },
                ],
            ];
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
    public function testAllMethodCallsAreDelegatedToTheWrappedInstance($method, array $parameters) : void
    {
        $stub = $this->wrapped
            ->expects(self::once())
            ->method($method);

        call_user_func_array([$stub, 'with'], $parameters);
        call_user_func_array([$this->decorator, $method], $parameters);
    }
}
