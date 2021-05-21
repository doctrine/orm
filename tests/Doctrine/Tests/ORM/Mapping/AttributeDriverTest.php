<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

use const PHP_VERSION_ID;

class AttributeDriverTest extends AbstractMappingDriverTest
{
    /** @before */
    public function requiresPhp8Assertion(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('requies PHP 8.0');
        }
    }

    protected function loadDriver(): MappingDriver
    {
        $paths = [];

        return new AttributeDriver($paths);
    }

    public function testNamedQuery(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named queries.');
    }

    public function testNamedNativeQuery(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named native queries.');
    }

    public function testSqlResultSetMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named sql resultset mapping.');
    }

    public function testAssociationOverridesMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testInversedByOverrideMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testFetchOverrideMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testAttributeOverridesMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testOriginallyNestedAttributesDeclaredWithoutOriginalParent(): void
    {
        $factory = $this->createClassMetadataFactory();

        $metadata = $factory->getMetadataFor(AttributeEntityWithoutOriginalParents::class);

        $this->assertEquals(
            [
                'name' => 'AttributeEntityWithoutOriginalParents',
                'uniqueConstraints' => ['foo' => ['columns' => ['id']]],
                'indexes' => ['bar' => ['columns' => ['id']]],
            ],
            $metadata->table
        );
        $this->assertEquals(['assoz_id', 'assoz_id'], $metadata->associationMappings['assoc']['joinTableColumns']);
    }
}

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'foo', columns: ['id'])]
#[ORM\Index(name: 'bar', columns: ['id'])]
class AttributeEntityWithoutOriginalParents
{
    #[ORM\Id, ORM\Column(type: 'integer'), ORM\GeneratedValue]
    /** @var int */
    public $id;

    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    #[ORM\InverseJoinColumn(name: 'assoz_id', referencedColumnName: 'assoz_id')]
    /** @var AttributeEntityWithoutOriginalParents[] */
    public $assoc;
}
