<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\Models\DDC2825\ExplicitSchemaAndTable;
use Doctrine\Tests\Models\DDC2825\SchemaAndTableInTableName;
use Doctrine\Tests\TestUtil;

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

        $platform = $this->_em->getConnection()->getDatabasePlatform();

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
        $classMetadata   = $this->_em->getClassMetadata($className);
        $platform        = $this->_em->getConnection()->getDatabasePlatform();
        $quotedTableName = $this->_em->getConfiguration()->getQuoteStrategy()->getTableName($classMetadata, $platform);

        // Check if table name and schema properties are defined in the class metadata
        $this->assertEquals($expectedTableName, $classMetadata->table['name']);
        $this->assertEquals($expectedSchemaName, $classMetadata->table['schema']);

        if ($this->_em->getConnection()->getDatabasePlatform()->supportsSchemas()) {
            $fullTableName = sprintf('%s.%s', $expectedSchemaName, $expectedTableName);
        } else {
            $fullTableName = sprintf('%s__%s', $expectedSchemaName, $expectedTableName);
        }

        $this->assertEquals($fullTableName, $quotedTableName);

        // Checks sequence name validity
        $this->assertEquals(
            $fullTableName . '_' . $classMetadata->getSingleIdentifierColumnName() . '_seq',
            $classMetadata->getSequenceName($platform)
        );
    }

    /**
     * @dataProvider getTestedClassesWithImplicitlyDefinedSchemaAndTableName
     *
     * @param string $className
     * @param string $expectedSchemaName
     * @param string $expectedTableName
     * @param string $expectedQuotedTableName
     */
    public function testClassSchemaMappingsValidityWithImplicitlyDefinedSchemaAndQuotedTableName(
        $className,
        $expectedSchemaName,
        $expectedTableName,
        $expectedQuotedTableName
    ) {
        $classMetadata   = $this->_em->getClassMetadata($className);
        $platform        = $this->_em->getConnection()->getDatabasePlatform();
        $quotedTableName = $this->_em->getConfiguration()->getQuoteStrategy()->getTableName($classMetadata, $platform);

        // Check if table name and schema properties are defined in the class metadata
        $this->assertEquals($expectedTableName, $classMetadata->table['name']);
        $this->assertEquals($expectedSchemaName, $classMetadata->table['schema']);

        if ($this->_em->getConnection()->getDatabasePlatform()->supportsSchemas()) {
            $fullTableName = sprintf('%s.%s', $expectedSchemaName, $expectedQuotedTableName);
        } else {
            $fullTableName = sprintf('%s__%s', $expectedSchemaName, $expectedTableName);
        }

        $this->assertEquals($fullTableName, $quotedTableName);
    }
    
    /**
     * @dataProvider getTestedClasses
     *
     * @param string $className
     */
    public function testPersistenceOfEntityWithSchemaMapping($className)
    {
        try {
            $this->_schemaTool->createSchema([$this->_em->getClassMetadata($className)]);
        } catch (ToolsException $e) {
            // table already exists
        }

        $this->_em->persist(new $className());
        $this->_em->flush();
        $this->_em->clear();

        $this->assertCount(1, $this->_em->getRepository($className)->findAll());
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
        ];
    }

    public function getTestedClassesWithImplicitlyDefinedSchemaAndTableName()
    {
        $connection = TestUtil::getConnection();
        $platform = $connection->getDatabasePlatform();

        $expectedQuotedTableName = 'postgresql' === $platform->getName() ? '"order"' : 'order';

        return [
            [DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::class, 'myschema', 'order', $expectedQuotedTableName],
        ];
    }
}

/**
 * @Entity
 * @Table(name="myschema.order")
 */
class DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     *
     * @var integer
     */
    public $id;
}
