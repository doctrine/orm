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

    public function testFetchingFromEntityWithExplicitlyDefinedSchemaInMappings()
    {
        // Test with a table with a schema
        $myEntity = new DDC2825MySchemaMyTable();

        $this->_em->persist($myEntity);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertCount(
            1,
            $this->_em->createQuery('SELECT mt FROM ' . DDC2825MySchemaMyTable::CLASSNAME . ' mt')->getResult()
        );
    }

    public function testFetchingFromEntityWithImplicitlyDefinedSchemaInMappings()
    {
        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaMyTable');
        $this->checkClassMetadata($classMetadata, 'myschema', 'mytable');

        // Test with schema defined directly as a table annotation property
        $myEntity2 = new DDC2825MySchemaMyTable2();

        $this->_em->persist($myEntity2);
        $this->_em->flush();
        $this->_em->clear();

        $entities = $this->_em->createQuery('SELECT mt2 FROM ' . __NAMESPACE__ . '\\DDC2825MySchemaMyTable2 mt2')->execute();
        $this->assertEquals(count($entities), 1);

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaMyTable2');
        $this->checkClassMetadata($classMetadata, 'myschema', 'mytable2');

        // Test with a table named "order" (which is a reserved keyword) to make sure the table name is not
        // incorrectly escaped when a schema is used and that the platform doesn't support schemas
        $order = new DDC2825MySchemaOrder();

        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->clear();

        $classMetadata = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2825MySchemaOrder');
        $this->checkClassMetadata($classMetadata, 'myschema', 'order');

        $entities = $this->_em->createQuery('SELECT mso FROM ' . __NAMESPACE__ . '\\DDC2825MySchemaOrder mso')->execute();
        $this->assertEquals(count($entities), 1);
    }

    /**
     * Checks that class metadata is correctly stored when a database schema is used and
     * checks that the table name is correctly converted whether the platform supports database
     * schemas or not
     *
     * @param  ClassMetadata $classMetadata Class metadata
     * @param  string $expectedSchemaName   Expected schema name
     * @param  string $expectedTableName    Expected table name
     */
    protected function checkClassMetadata(ClassMetadata $classMetadata, $expectedSchemaName, $expectedTableName)
    {
        $quoteStrategy   = $this->_em->getConfiguration()->getQuoteStrategy();
        $platform        = $this->_em->getConnection()->getDatabasePlatform();
        $quotedTableName = $quoteStrategy->getTableName($classMetadata, $platform);

        // Check if table name and schema properties are defined in the class metadata
        $this->assertEquals($classMetadata->table['name'], $expectedTableName);
        $this->assertEquals($classMetadata->table['schema'], $expectedSchemaName);

        if ($this->_em->getConnection()->getDatabasePlatform()->supportsSchemas()) {
            $fullTableName = sprintf('%s.%s', $expectedSchemaName, $expectedTableName);
        } else {
            $fullTableName = sprintf('%s__%s', $expectedSchemaName, $expectedTableName);
        }

        $this->assertEquals($quotedTableName, $fullTableName);

        // Checks sequence name validity
        $expectedSchemaName = $fullTableName . '_' . $classMetadata->getSingleIdentifierColumnName() . '_seq';
        $this->assertEquals($expectedSchemaName, $classMetadata->getSequenceName($platform));
    }
}

/**
 * @Entity
 * @Table(name="myschema.mytable")
 */
class DDC2825MySchemaMyTable
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
class DDC2825MySchemaMyTable2
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
class DDC2825MySchemaOrder
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
