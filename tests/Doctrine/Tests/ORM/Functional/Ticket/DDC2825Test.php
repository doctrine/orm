<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;

/**
 * This class makes tests on the correct use of a database schema when entities are stored
 *
 * @group DDC-2825
 */
class DDC2825Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $platform = $this->em->getConnection()->getDatabasePlatform();

        if ( ! $platform->supportsSchemas() && ! $platform->canEmulateSchemas()) {
            $this->markTestSkipped("This test is only useful for databases that support schemas or can emulate them.");
        }
    }

    /**
     * @dataProvider getTestedClasses
     *
     * @param string $className
     * @param string $expectedSchemaName
     * @param string $expectedTableName
     */
    public function testClassSchemaMappingsValidity($className, $expectedSchemaName, $expectedTableName)
    {
        $classMetadata   = $this->em->getClassMetadata($className);
        $platform        = $this->em->getConnection()->getDatabasePlatform();
        $quotedTableName = $classMetadata->table->getQuotedQualifiedName($platform);

        // Check if table name and schema properties are defined in the class metadata
        self::assertEquals($expectedTableName, $classMetadata->table->getName());
        self::assertEquals($expectedSchemaName, $classMetadata->table->getSchema());

        if ($platform->supportsSchemas()) {
            $fullTableName = sprintf('"%s"."%s"', $expectedSchemaName, $expectedTableName);
        } else {
            $fullTableName = sprintf('"%s__%s"', $expectedSchemaName, $expectedTableName);
        }

        self::assertEquals($fullTableName, $quotedTableName);

        // Checks sequence name validity
        self::assertEquals(
            str_replace('"', '', $fullTableName) . '_' . $classMetadata->getSingleIdentifierColumnName() . '_seq',
            $classMetadata->getSequenceName($platform)
        );
    }

    /**
     * @dataProvider getTestedClasses
     *
     * @param string $className
     */
    public function testPersistenceOfEntityWithSchemaMapping($className)
    {
        $classMetadata = $this->em->getClassMetadata($className);
        $repository    = $this->em->getRepository($className);

        try {
            $this->schemaTool->createSchema([$classMetadata]);
        } catch (ToolsException $e) {
            // table already exists
        }

        $this->em->persist(new $className());
        $this->em->flush();
        $this->em->clear();

        self::assertCount(1, $repository->findAll());
    }

    /**
     * Data provider
     *
     * @return string[][]
     */
    public function getTestedClasses()
    {
        return [
            [ExplicitSchemaAndTable::class, 'explicit_schema', 'explicit_table'],
            [SchemaAndTableInTableName::class, 'implicit_schema', 'implicit_table'],
            [DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::class, 'myschema', 'order'],
        ];
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="order", schema="myschema")
 */
class DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    public $id;
}
