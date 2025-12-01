<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * This class makes tests on the correct use of a database schema when entities are stored
 */
#[Group('DDC-2825')]
class DDC2825Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->_em->getConnection()->getDatabasePlatform();

        if (! $platform->supportsSchemas()) {
            self::markTestSkipped('This test is only useful for databases that support schemas or can emulate them.');
        }
    }

    /** @param class-string $className */
    #[DataProvider('getTestedClasses')]
    public function testPersistenceOfEntityWithSchemaMapping(string $className): void
    {
        $this->createSchemaForModels($className);

        $this->_em->persist(new $className());
        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(1, $this->_em->getRepository($className)->findAll());
    }

    /** @return list<array{class-string}> */
    public static function getTestedClasses(): array
    {
        return [
            [ExplicitSchemaAndTable::class],
            [SchemaAndTableInTableName::class],
            [DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::class],
            [File::class],
        ];
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'myschema.order')]
class DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;
}

#[ORM\Entity]
#[ORM\Table(name: '`file`', schema: 'yourschema')]
class File
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int $id;
}
