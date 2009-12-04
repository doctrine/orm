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
        $table = new \Doctrine\DBAL\Schema\Table("dbdriver_foo");
        $table->createColumn('id', 'integer');
        $table->setPrimaryKey(array('id'));
        $table->createColumn('bar', 'string', array('length' => 200));

        $this->_sm->dropAndCreateTable($table);

        $this->assertClassMetadataYamlEqualsFile(__DIR__."/DatabaseDriver/simpleYaml.yml", "DbdriverFoo");
    }

    protected function assertClassMetadataYamlEqualsFile($file, $className)
    {
        $cm = new ClassMetadataExporter();
        $cm->addMappingSource($this->_sm, 'database');
        $exporter = $cm->getExporter('yaml');
        $metadatas = $cm->getMetadatasForMappingSources();

        $output = false;
        foreach ($metadatas AS $metadata) {
            if ($metadata->name == $className) {
                $output = $exporter->exportClassMetadata($metadata);
            }
        }
        
        $this->assertTrue($output!==false, "No class matching the name '".$className."' was found!");
        $this->assertEquals(strtolower(trim(file_get_contents($file))), strtolower(trim($output)));
    }

    public function testCreateYamlWithForeignKeyFromDatabase()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $tableB = new \Doctrine\DBAL\Schema\Table("dbdriver_bar");
        $tableB->createColumn('id', 'integer');
        $tableB->setPrimaryKey(array('id'));

        $sm = $this->_em->getConnection()->getSchemaManager();
        $sm->dropAndCreateTable($tableB);

        $tableA = new \Doctrine\DBAL\Schema\Table("dbdriver_baz");
        $tableA->createColumn('id', 'integer');
        $tableA->setPrimaryKey(array('id'));
        $tableA->createColumn('bar_id', 'integer');
        $tableA->addForeignKeyConstraint('dbdriver_bar', array('bar_id'), array('id'));

        $this->_sm = $this->_em->getConnection()->getSchemaManager();
        $this->_sm->dropAndCreateTable($tableA);

        $this->assertClassMetadataYamlEqualsFile(__DIR__."/DatabaseDriver/fkYaml.yml", "DbdriverBaz");
    }
}
