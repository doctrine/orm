<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\ORMInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function spl_object_id;

/** @covers \Doctrine\ORM\ORMInvalidArgumentException */
class ORMInvalidArgumentExceptionTest extends TestCase
{
    /**
     * @param mixed $value
     *
     * @dataProvider invalidEntityNames
     */
    public function testInvalidEntityName($value, string $expectedMessage): void
    {
        $exception = ORMInvalidArgumentException::invalidEntityName($value);

        self::assertInstanceOf(ORMInvalidArgumentException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /** @psalm-return list<array{mixed, string}> */
    public static function invalidEntityNames(): array
    {
        return [
            [null, 'Entity name must be a string, null given'],
            [true, 'Entity name must be a string, bool given'],
            [123, 'Entity name must be a string, int given'],
            [123.45, 'Entity name must be a string, float given'],
            [new stdClass(), 'Entity name must be a string, stdClass given'],
        ];
    }

    /** @dataProvider newEntitiesFoundThroughRelationshipsErrorMessages */
    public function testNewEntitiesFoundThroughRelationships(array $newEntities, string $expectedMessage): void
    {
        $exception = ORMInvalidArgumentException::newEntitiesFoundThroughRelationships($newEntities);

        self::assertInstanceOf(ORMInvalidArgumentException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    public static function newEntitiesFoundThroughRelationshipsErrorMessages(): array
    {
        $entity1      = new stdClass();
        $entity2      = new stdClass();
        $entity3      = new class {
            public function __toString(): string
            {
                return 'ThisIsAStringRepresentationOfEntity3';
            }
        };
        $association1 = [
            'sourceEntity' => 'foo1',
            'fieldName'    => 'bar1',
            'targetEntity' => 'baz1',
        ];
        $association2 = [
            'sourceEntity' => 'foo2',
            'fieldName'    => 'bar2',
            'targetEntity' => 'baz2',
        ];
        $association3 = [
            'sourceEntity' => 'foo3',
            'fieldName'    => 'bar3',
            'targetEntity' => 'baz3',
        ];

        return [
            'one entity found' => [
                [
                    [
                        $association1,
                        $entity1,
                    ],
                ],
                'A new entity was found through the relationship \'foo1#bar1\' that was not configured to cascade '
                . 'persist operations for entity: stdClass@' . spl_object_id($entity1)
                . '. To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity '
                . 'or configure cascade persist this association in the mapping for example '
                . '@ManyToOne(..,cascade={"persist"}). If you cannot find out which entity causes the problem '
                . 'implement \'baz1#__toString()\' to get a clue.',
            ],
            'two entities found' => [
                [
                    [
                        $association1,
                        $entity1,
                    ],
                    [
                        $association2,
                        $entity2,
                    ],
                ],
                'Multiple non-persisted new entities were found through the given association graph:' . "\n\n"
                . ' * A new entity was found through the relationship \'foo1#bar1\' that was not configured to '
                . 'cascade persist operations for entity: stdClass@' . spl_object_id($entity1) . '. '
                . 'To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity '
                . 'or configure cascade persist this association in the mapping for example '
                . '@ManyToOne(..,cascade={"persist"}). If you cannot find out which entity causes the problem '
                . 'implement \'baz1#__toString()\' to get a clue.' . "\n"
                . ' * A new entity was found through the relationship \'foo2#bar2\' that was not configured to '
                . 'cascade persist operations for entity: stdClass@' . spl_object_id($entity2) . '. To solve '
                . 'this issue: Either explicitly call EntityManager#persist() on this unknown entity or '
                . 'configure cascade persist this association in the mapping for example '
                . '@ManyToOne(..,cascade={"persist"}). If you cannot find out which entity causes the problem '
                . 'implement \'baz2#__toString()\' to get a clue.',
            ],
            'two entities found, one is stringable' => [
                [
                    [
                        $association3,
                        $entity3,
                    ],
                ],
                'A new entity was found through the relationship \'foo3#bar3\' that was not configured to cascade '
                . 'persist operations for entity: ThisIsAStringRepresentationOfEntity3'
                . '. To solve this issue: Either explicitly call EntityManager#persist() on this unknown entity '
                . 'or configure cascade persist this association in the mapping for example '
                . '@ManyToOne(..,cascade={"persist"}).',
            ],
        ];
    }
}
