<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\ToolsException;

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
    protected function setup()
    {
        parent::setup();

        $platform = $this->_em->getConnection()->getDatabasePlatform();

        if ( ! $platform->supportsSchemas() && ! $platform->canEmulateSchemas()) {
            $this->markTestSkipped("This test is only useful for databases that support schemas or can emulate them.");
        }

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaMyTable'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaMyTable2'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaOrder'),
            ));
        } catch (ToolsException $e) {
            // tables already exist
        }
    }

    public function testMappingsContainCorrectlyDefinedOrEmulatedSchema()
    {
        $this->checkClassMetadata(DDC2825ClassWithExplicitlyDefinedSchema::CLASSNAME, 'myschema', 'mytable');
        $this->checkClassMetadata(DDC2825ClassWithImplicitlyDefinedSchema::CLASSNAME, 'myschema', 'mytable2');
        $this->checkClassMetadata(DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::CLASSNAME, 'myschema', 'order');
    }

    /**
     * Test with a table with a schema
     */
    public function testFetchingFromEntityWithExplicitlyDefinedSchemaInMappings()
    {
        $this->_em->persist(new DDC2825ClassWithExplicitlyDefinedSchema());
        $this->_em->flush();
        $this->_em->clear();

        $this->assertCount(
            1,
            $this->_em->getRepository(DDC2825ClassWithExplicitlyDefinedSchema::CLASSNAME)->findAll()
        );
    }

    /**
     * Test with schema defined directly as a table annotation property
     */
    public function testFetchingFromEntityWithImplicitlyDefinedSchemaInMappings()
    {
        $this->_em->persist(new DDC2825ClassWithImplicitlyDefinedSchema());
        $this->_em->flush();
        $this->_em->clear();

        $this->assertCount(
            1,
            $this->_em->getRepository(DDC2825ClassWithImplicitlyDefinedSchema::CLASSNAME)->findAll()
        );
    }

    /**
     * Test with a table named "order" (which is a reserved keyword) to make sure the table name is not
     * incorrectly escaped when a schema is used and that the platform doesn't support schemas
     */
    public function testFetchingFromEntityWithImplicitlyDefinedSchemaAndQuotedTableNameInMappings()
    {
        $this->_em->persist(new DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName());
        $this->_em->flush();
        $this->_em->clear();

        $this->assertCount(
            1,
            $this->_em->getRepository(DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName::CLASSNAME)->findAll()
        );
    }

    /**
     * Checks that class metadata is correctly stored when a database schema is used and
     * checks that the table name is correctly converted whether the platform supports database
     * schemas or not
     *
     * @param  string $className Class metadata
     * @param  string $expectedSchemaName   Expected schema name
     * @param  string $expectedTableName    Expected table name
     */
    protected function checkClassMetadata($className, $expectedSchemaName, $expectedTableName)
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
}

/**
 * @Entity
 * @Table(name="myschema.mytable")
 */
class DDC2825ClassWithExplicitlyDefinedSchema
{
    const CLASSNAME = __CLASS__;

    /**
     * Test with a quoted column name to check that sequence names are
     * correctly handled
     *
     * @Id @GeneratedValue
     * @Column(name="`number`", type="integer")
     *
     * @var integer
     */
    public $id;
}

/**
 * @Entity
 * @Table(name="mytable2",schema="myschema")
 */
class DDC2825ClassWithImplicitlyDefinedSchema
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     *
     * @var integer
     */
    public $id;
}


/**
 * @Entity
 * @Table(name="myschema.order")
 */
class DDC2825ClassWithImplicitlyDefinedSchemaAndQuotedTableName
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     *
     * @var integer
     */
    public $id;
}
