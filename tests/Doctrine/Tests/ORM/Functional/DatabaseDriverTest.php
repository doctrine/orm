<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\ORM\Tools\Export\ClassMetadataExporter;

class DatabaseDriverTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $_sm = null;

    public function setUp()
    {
        parent::setUp();

        $this->_sm = $this->_em->getConnection()->getSchemaManager();
    }

    public function testCreateSimpleYamlFromDatabase()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $table = new \Doctrine\DBAL\Schema\Table("dbdriver_foo");
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(array('id'));
        $table->addColumn('bar', 'string', array('length' => 200));

        $this->_sm->dropAndCreateTable($table);

        $metadata = $this->extractClassMetadata("DbdriverFoo");

        $this->assertArrayHasKey('id',          $metadata->fieldMappings);
        $this->assertEquals('id',               $metadata->fieldMappings['id']['fieldName']);
        $this->assertEquals('id',               strtolower($metadata->fieldMappings['id']['columnName']));
        $this->assertEquals('integer',          (string)$metadata->fieldMappings['id']['type']);
        $this->assertTrue($metadata->fieldMappings['id']['notnull']);

        $this->assertArrayHasKey('bar',         $metadata->fieldMappings);
        $this->assertEquals('bar',              $metadata->fieldMappings['bar']['fieldName']);
        $this->assertEquals('bar',              strtolower($metadata->fieldMappings['bar']['columnName']));
        $this->assertEquals('string',           (string)$metadata->fieldMappings['bar']['type']);
        $this->assertEquals(200,                $metadata->fieldMappings['bar']['length']);
        $this->assertTrue($metadata->fieldMappings['bar']['notnull']);
    }

    public function testCreateYamlWithForeignKeyFromDatabase()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $tableB = new \Doctrine\DBAL\Schema\Table("dbdriver_bar");
        $tableB->addColumn('id', 'integer');
        $tableB->setPrimaryKey(array('id'));

        $this->_sm->dropAndCreateTable($tableB);

        $tableA = new \Doctrine\DBAL\Schema\Table("dbdriver_baz");
        $tableA->addColumn('id', 'integer');
        $tableA->setPrimaryKey(array('id'));
        $tableA->addColumn('bar_id', 'integer');
        $tableA->addForeignKeyConstraint('dbdriver_bar', array('bar_id'), array('id'));

        $this->_sm->dropAndCreateTable($tableA);

        $metadata = $this->extractClassMetadata("DbdriverBaz");

        $this->assertArrayNotHasKey('bar', $metadata->fieldMappings);
        $this->assertArrayHasKey('id', $metadata->fieldMappings);

        $metadata->associationMappings = \array_change_key_case($metadata->associationMappings, \CASE_LOWER);

        $this->assertArrayHasKey('bar', $metadata->associationMappings);
        $this->assertType('Doctrine\ORM\Mapping\OneToOneMapping', $metadata->associationMappings['bar']);
    }


    /**
     *
     * @param  string $className
     * @return ClassMetadata
     */
    protected function extractClassMetadata($className)
    {
        $cm = new ClassMetadataExporter();
        $cm->addMappingSource($this->_sm, 'database');
        $exporter = $cm->getExporter('yaml');
        $metadatas = $cm->getMetadatasForMappingSources();

        $output = false;
        
        foreach ($metadatas AS $metadata) {
            if (strtolower($metadata->name) == strtolower($className)) {
                return $metadata;
            }
        }

        $this->fail("No class matching the name '".$className."' was found!");
    }
}
