<?php

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\VerifyDeprecations;
use PHPUnit\Framework\TestCase;
use function in_array;

class EntityManagerDecoratorTest extends TestCase
{
    use VerifyDeprecations;

    const VOID_METHODS = [
        'persist',
        'remove',
        'clear',
        'detach',
        'refresh',
        'flush',
        'initializeObject',
    ];

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrapped;

    /** @before */
    public function ignoreDeprecationMessagesFromDoctrinePersistence() : void
    {
        $this->ignoreDeprecationMessage('The Doctrine\Common\Persistence\ObjectManagerDecorator class is deprecated since doctrine/persistence 1.3 and will be removed in 2.0. Use \Doctrine\Persistence\ObjectManagerDecorator instead.');
    }

    public function setUp()
    {
        $this->wrapped = $this->createMock(EntityManagerInterface::class);
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

        if ($method->getNumberOfRequiredParameters() === 0) {
            return [$method->getName(), []];
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            return [$method->getName(), array_fill(0, $method->getNumberOfRequiredParameters(), 'req') ?: []];
        }

        if ($method->getNumberOfParameters() != $method->getNumberOfRequiredParameters()) {
            return [$method->getName(), array_fill(0, $method->getNumberOfParameters(), 'all') ?: []];
        }

        return [];
    }

    /**
     * @dataProvider getMethodParameters
     */
    public function testAllMethodCallsAreDelegatedToTheWrappedInstance($method, array $parameters)
    {
        $return = !in_array($method, self::VOID_METHODS) ? 'INNER VALUE FROM ' . $method : null;

        $this->wrapped->expects($this->once())
            ->method($method)
            ->with(...$parameters)
            ->willReturn($return);

        $decorator = new class ($this->wrapped) extends EntityManagerDecorator {
        };

        $this->assertSame($return, $decorator->$method(...$parameters));

        if (in_array($method, ['copy', 'merge', 'detach', 'getHydrator'], true)) {
            $this->assertHasDeprecationMessages();
            return;
        }

        $this->assertNotHasDeprecationMessages();
    }
}
