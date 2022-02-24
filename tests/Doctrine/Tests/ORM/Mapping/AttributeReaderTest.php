<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @requires PHP 8.0
 */
class AttributeReaderTest extends TestCase
{
    public function testItThrowsWhenGettingRepeatableAnnotationWithTheWrongMethod(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'id');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The attribute "Doctrine\ORM\Mapping\Index" is repeatable. Call getPropertyAnnotationCollection() instead.'
        );
        $reader->getPropertyAnnotation($property, ORM\Index::class);
    }

    public function testItThrowsWhenGettingNonRepeatableAnnotationWithTheWrongMethod(): void
    {
        $reader   = new AttributeReader();
        $property = new ReflectionProperty(TestEntity::class, 'id');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The attribute "Doctrine\ORM\Mapping\Id" is not repeatable. Call getPropertyAnnotation() instead.'
        );
        $reader->getPropertyAnnotationCollection($property, ORM\Id::class);
    }
}

#[ORM\Entity]
#[ORM\Index(name: 'bar', columns: ['id'])]
class TestEntity
{
    #[ORM\Id, ORM\Column(type: 'integer'), ORM\GeneratedValue]
    /** @var int */
    public $id;
}
