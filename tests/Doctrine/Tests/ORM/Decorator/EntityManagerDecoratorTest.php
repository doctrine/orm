<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Generator;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use stdClass;

use function assert;
use function in_array;
use function sprintf;

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

    /** @psalm-return Generator<string, mixed[]> */
    public static function getMethodParameters(): Generator
    {
        $class = new ReflectionClass(EntityManagerInterface::class);

        foreach ($class->getMethods() as $method) {
            if ($method->isConstructor() || $method->isStatic() || ! $method->isPublic()) {
                continue;
            }

            yield $method->getName() => self::getParameters($method);
        }
    }

    /** @return mixed[] */
    private static function getParameters(ReflectionMethod $method): array
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

        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getType() === null) {
                $parameters[] = 'mixed';
                continue;
            }

            $type = $parameter->getType();
            assert($type instanceof ReflectionNamedType);
            switch ($type->getName()) {
                case 'string':
                    $parameters[] = 'parameter';
                    break;

                case 'object':
                    $parameters[] = new stdClass();
                    break;

                default:
                    throw new LogicException(sprintf(
                        'Type %s is not handled yet',
                        (string) $parameter->getType()
                    ));
            }
        }

        return [$method->getName(), $parameters];
    }

    /** @dataProvider getMethodParameters */
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
