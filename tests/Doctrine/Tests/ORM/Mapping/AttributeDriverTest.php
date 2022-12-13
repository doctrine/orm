<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Attribute;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver as PersistenceAnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use stdClass;

use function class_exists;
use function is_subclass_of;

use const PHP_VERSION_ID;

class AttributeDriverTest extends MappingDriverTestCase
{
    /** @before */
    public function requiresPhp8Assertion(): void
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped('requires PHP 8.0');
        }
    }

    protected function loadDriver(): MappingDriver
    {
        $paths = [];

        return new AttributeDriver($paths);
    }

    public function testNamedQuery(): void
    {
        self::markTestSkipped('AttributeDriver does not support named queries.');
    }

    public function testNamedNativeQuery(): void
    {
        self::markTestSkipped('AttributeDriver does not support named native queries.');
    }

    public function testSqlResultSetMapping(): void
    {
        self::markTestSkipped('AttributeDriver does not support named sql resultset mapping.');
    }

    public function testAssociationOverridesMapping(): void
    {
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('AttributeDriver does not support association overrides.');
        } else {
            parent::testAssociationOverridesMapping();
        }
    }

    public function testInversedByOverrideMapping(): void
    {
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('AttributeDriver does not support association overrides.');
        } else {
            parent::testInversedByOverrideMapping();
        }
    }

    public function testFetchOverrideMapping(): void
    {
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('AttributeDriver does not support association overrides.');
        } else {
            parent::testFetchOverrideMapping();
        }
    }

    public function testAttributeOverridesMapping(): void
    {
        if (PHP_VERSION_ID < 80100) {
            self::markTestSkipped('AttributeDriver does not support association overrides.');
        } else {
            parent::testAttributeOverridesMapping();
        }
    }

    public function testOriginallyNestedAttributesDeclaredWithoutOriginalParent(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor(AttributeEntityWithoutOriginalParents::class);

        self::assertEquals(
            [
                'name' => 'AttributeEntityWithoutOriginalParents',
                'uniqueConstraints' => ['foo' => ['columns' => ['id']]],
                'indexes' => ['bar' => ['columns' => ['id']]],
            ],
            $metadata->table
        );
        self::assertEquals(['assoz_id', 'assoz_id'], $metadata->associationMappings['assoc']['joinTableColumns']);
    }

    public function testIsTransient(): void
    {
        $driver = $this->loadDriver();

        self::assertTrue($driver->isTransient(stdClass::class));

        self::assertTrue($driver->isTransient(AttributeTransientClass::class));

        self::assertFalse($driver->isTransient(AttributeEntityWithoutOriginalParents::class));

        self::assertFalse($driver->isTransient(AttributeEntityStartingWithRepeatableAttributes::class));
    }

    public function testLegacyInheritance(): void
    {
        if (! class_exists(PersistenceAnnotationDriver::class)) {
            self::markTestSkipped('This test requires doctrine/persistence 2.');
        }

        self::assertTrue(is_subclass_of(AttributeDriver::class, PersistenceAnnotationDriver::class));
    }
}

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'foo', columns: ['id'])]
#[ORM\Index(name: 'bar', columns: ['id'])]
class AttributeEntityWithoutOriginalParents
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var AttributeEntityWithoutOriginalParents[] */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    #[ORM\InverseJoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    public $assoc;
}

#[ORM\Index(name: 'bar', columns: ['id'])]
#[ORM\Index(name: 'baz', columns: ['id'])]
#[ORM\Entity]
class AttributeEntityStartingWithRepeatableAttributes
{
}

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class AttributeTransientAnnotation implements MappingAttribute
{
}

#[AttributeTransientAnnotation]
class AttributeTransientClass
{
}
