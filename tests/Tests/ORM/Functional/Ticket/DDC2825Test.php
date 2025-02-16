<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function sprintf;

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

    #[DataProvider('getTestedClasses')]
    public function testClassSchemaMappingsValidity(string $className, string $expectedSchemaName, string $expectedTableName): void
    {
        $classMetadata   = $this->_em->getClassMetadata($className);
        $platform        = $this->_em->getConnection()->getDatabasePlatform();
        $quotedTableName = $this->_em->getConfiguration()->getQuoteStrategy()->getTableName($classMetadata, $platform);

        // Check if table name and schema properties are defined in the class metadata
        self::assertEquals($expectedTableName, $classMetadata->table['name']);
        self::assertEquals($expectedSchemaName, $classMetadata->table['schema']);

        if ($this->_em->getConnection()->getDatabasePlatform()->supportsSchemas()) {
            $fullTableName = sprintf('%s.%s', $expectedSchemaName, $expectedTableName);
        } else {
            $fullTableName = sprintf('%s__%s', $expectedSchemaName, $expectedTableName);
        }

        self::assertEquals($fullTableName, $quotedTableName);

        // Checks sequence name validity
        self::assertEquals(
            $fullTableName . '_' . $classMetadata->getSingleIdentifierColumnName() . '_seq',
            $classMetadata->getSequenceName($platform),
        );
    }

    #[DataProvider('getTestedClasses')]
    public function testPersistenceOfEntityWithSchemaMapping(string $className): void
    {
        $this->createSchemaForModels($className);

        $this->_em->persist(new $className());
        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(1, $this->_em->getRepository($className)->findAll());
    }

    /**
     * Data provider
     *
     * @return string[][]
     */
    public static function getTestedClasses(): array
    {
        return [
            [ExplicitSchemaAndTable::class, 'explicit_schema', 'explicit_table'],
            [SchemaAndTableInTableName::class, 'implicit_schema', 'implicit_table'],
            [DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::class, 'myschema', 'order'],
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
