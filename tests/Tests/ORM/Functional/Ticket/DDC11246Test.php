<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-11246')]
class DDC11246Test extends OrmFunctionalTestCase
{
    /** @param class-string $className */
    #[DataProvider('classesWithoutUniqueConstraintName')]
    public function testUniqueConstraintWithoutName(string $className): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $classes = [
            $em->getClassMetadata($className),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('unique-constraint-without-name'));
        $table = $schema->getTable('unique-constraint-without-name');

        self::assertCount(2, $table->getIndexes());
        self::assertTrue($table->columnsAreIndexed(['name', 'phone']));
    }

    public static function classesWithoutUniqueConstraintName(): Generator
    {
        yield 'Entity class that passes fields for the UniqueConstraint attribute.' => [DDC11246FieldsEntity::class];
        yield 'Entity class that passes columns for the UniqueConstraint attribute.' => [DDC11246ColumnsEntity::class];
    }
}

#[Entity]
#[Table('unique-constraint-without-name')]
#[UniqueConstraint(fields: ['name', 'phone'])]
class DDC11246FieldsEntity
{
    #[Id]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $phone;

    #[Column(type: 'string')]
    public string $email;
}

#[Entity]
#[Table('unique-constraint-without-name')]
#[UniqueConstraint(columns: ['name', 'phone'])]
class DDC11246ColumnsEntity
{
    #[Id]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $phone;

    #[Column(type: 'string')]
    public string $email;
}
