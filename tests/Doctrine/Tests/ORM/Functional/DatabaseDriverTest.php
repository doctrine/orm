<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Inflector;

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

    public function testLoadMetadataFromDatabase()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $table = new \Doctrine\DBAL\Schema\Table("dbdriver_foo");
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(array('id'));
        $table->addColumn('bar', 'string', array('notnull' => false, 'length' => 200));

        $this->_sm->dropAndCreateTable($table);

        $metadatas = $this->extractClassMetadata(array("DbdriverFoo"));

        $this->assertArrayHasKey('dbdriver_foo', $metadatas);
        $metadata = $metadatas['dbdriver_foo'];

        $this->assertArrayHasKey('id',          $metadata->fieldMappings);
        $this->assertEquals('id',               $metadata->fieldMappings['id']['fieldName']);
        $this->assertEquals('id',               strtolower($metadata->fieldMappings['id']['columnName']));
        $this->assertEquals('integer',          (string)$metadata->fieldMappings['id']['type']);

        $this->assertArrayHasKey('bar',         $metadata->fieldMappings);
        $this->assertEquals('bar',              $metadata->fieldMappings['bar']['fieldName']);
        $this->assertEquals('bar',              strtolower($metadata->fieldMappings['bar']['columnName']));
        $this->assertEquals('string',           (string)$metadata->fieldMappings['bar']['type']);
        $this->assertEquals(200,                $metadata->fieldMappings['bar']['length']);
        $this->assertTrue($metadata->fieldMappings['bar']['nullable']);
    }

    public function testLoadMetadataWithForeignKeyFromDatabase()
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

        $metadatas = $this->extractClassMetadata(array("DbdriverBar", "DbdriverBaz"));

        $this->assertArrayHasKey('dbdriver_baz', $metadatas);
        $bazMetadata = $metadatas['dbdriver_baz'];

        $this->assertArrayNotHasKey('barId', $bazMetadata->fieldMappings, "The foreign Key field should not be inflected as 'barId' field, its an association.");
        $this->assertArrayHasKey('id', $bazMetadata->fieldMappings);

        $bazMetadata->associationMappings = \array_change_key_case($bazMetadata->associationMappings, \CASE_LOWER);

        $this->assertArrayHasKey('bar', $bazMetadata->associationMappings);
        $this->assertType('Doctrine\ORM\Mapping\OneToOneMapping', $bazMetadata->associationMappings['bar']);
    }

    /**
     *
     * @param  string $className
     * @return ClassMetadata
     */
    protected function extractClassMetadata(array $classNames)
    {
        $classNames = array_map('strtolower', $classNames);
        $metadatas = array();

        $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($this->_sm);
        foreach ($driver->getAllClassNames() as $dbTableName) {
            if (!in_array(strtolower(Inflector::classify($dbTableName)), $classNames)) {
                continue;
            }
            $class = new ClassMetadataInfo($dbTableName);
            $driver->loadMetadataForClass($dbTableName, $class);
            $metadatas[strtolower($dbTableName)] = $class;
        }

        if (count($metadatas) != count($classNames)) {
            $this->fail("Have not found all classes matching the names '" . implode(", ", $classNames) . "' only tables " . implode(", ", array_keys($metadatas)));
        }
        return $metadatas;
    }
}
