<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

use function array_fill;
use function in_array;

class EntityManagerDecoratorTest extends TestCase
{
    public const VOID_METHODS = [
        'persist',
        'remove',
        'clear',
        'detach',
        'refresh',
        'flush',
        'initializeObject',
        'beginTransaction',
        'commit',
        'rollback',
        'close',
        'lock',
    ];

    /** @var EntityManagerInterface&MockObject */
    private $wrapped;

    protected function setUp(): void
    {
        $this->wrapped = $this->createMock(EntityManagerInterface::class);
    }

    /** @psalm-return array<string, mixed[]> */
    public function getMethodParameters(): array
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

    /**
     * @return mixed[]
     */
    private function getParameters(ReflectionMethod $method): array
    {
        /** Special case EntityManager::createNativeQuery() */
        if ($method->getName() === 'createNativeQuery') {
            return [$method->getName(), ['name', new ResultSetMapping()]];
        }

        if ($method->getName() === 'wrapInTransaction') {
            return [
                $method->getName(),
                [
                    static function (): void {
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
    public function testAllMethodCallsAreDelegatedToTheWrappedInstance($method, array $parameters): void
    {
        $return = ! in_array($method, self::VOID_METHODS, true) ? 'INNER VALUE FROM ' . $method : null;

        $this->wrapped->expects(self::once())
            ->method($method)
            ->with(...$parameters)
            ->willReturn($return);

        $decorator = new class ($this->wrapped) extends EntityManagerDecorator {
        };

        self::assertSame($return, $decorator->$method(...$parameters));
    }
}
