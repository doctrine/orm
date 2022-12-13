<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @requires PHP 8.0
 */
class AttributeReaderTest extends TestCase
{
    public function testItThrowsWhenGettingRepeatableAttributeWithTheWrongMethod(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'id');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The attribute "Doctrine\ORM\Mapping\Index" is repeatable. Call getPropertyAttributeCollection() instead.'
        );
        $reader->getPropertyAttribute($property, ORM\Index::class);
    }

    public function testItThrowsWhenGettingNonRepeatableAttributeWithTheWrongMethod(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'id');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The attribute "Doctrine\ORM\Mapping\Id" is not repeatable. Call getPropertyAttribute() instead.'
        );
        $reader->getPropertyAttributeCollection($property, ORM\Id::class);
    }

    public function testJoinTableOptions(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'tags');

        $joinTable = $reader->getPropertyAttribute($property, ORM\JoinTable::class);
        self::assertSame([
            'charset' => 'ascii',
            'collation' => 'ascii_general_ci',
        ], $joinTable->options);
    }

    public function testJoinColumnOptions(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'tags');

        $joinColumns = $reader->getPropertyAttributeCollection($property, ORM\JoinColumn::class);
        self::assertCount(1, $joinColumns);
        self::assertSame([
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
        ], $joinColumns[0]->options);

        $inverseJoinColumns = $reader->getPropertyAttributeCollection($property, ORM\InverseJoinColumn::class);
        self::assertCount(1, $inverseJoinColumns);
        self::assertSame([
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
        ], $inverseJoinColumns[0]->options);
    }
}

#[ORM\Entity]
#[ORM\Index(name: 'bar', columns: ['id'])]
class TestEntity
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var mixed */
    #[ManyToMany(targetEntity: TestTag::class)]
    #[JoinTable(name: 'artist_tags', options: ['charset' => 'ascii', 'collation' => 'ascii_general_ci'])]
    #[JoinColumn(name: 'artist_id', referencedColumnName: 'id', options: ['charset' => 'latin1', 'collation' => 'latin1_swedish_ci'])]
    #[InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', options: ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin'])]
    public $tags;
}

#[ORM\Entity]
class TestTag
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;
}
