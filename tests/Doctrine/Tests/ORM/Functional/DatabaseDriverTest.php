<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DatabaseDriverTest extends DatabaseDriverTestCase
{
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $_sm = null;

    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->_sm = $this->_em->getConnection()->getSchemaManager();
    }

    /**
     * @group DDC-2059
     */
    public function testIssue2059()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $user = new \Doctrine\DBAL\Schema\Table("ddc2059_user");
        $user->addColumn('id', 'integer');
        $user->setPrimaryKey(array('id'));
        $project = new \Doctrine\DBAL\Schema\Table("ddc2059_project");
        $project->addColumn('id', 'integer');
        $project->addColumn('user_id', 'integer');
        $project->addColumn('user', 'string');
        $project->setPrimaryKey(array('id'));
        $project->addForeignKeyConstraint('ddc2059_user', array('user_id'), array('id'));

        $metadata = $this->convertToClassMetadata(array($project, $user), array());

        $this->assertTrue(isset($metadata['Ddc2059Project']->fieldMappings['user']));
        $this->assertTrue(isset($metadata['Ddc2059Project']->associationMappings['user2']));
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

        $this->assertArrayHasKey('DbdriverFoo', $metadatas);
        $metadata = $metadatas['DbdriverFoo'];

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

        $this->assertArrayHasKey('DbdriverBaz', $metadatas);
        $bazMetadata = $metadatas['DbdriverBaz'];

        $this->assertArrayNotHasKey('barId', $bazMetadata->fieldMappings, "The foreign Key field should not be inflected as 'barId' field, its an association.");
        $this->assertArrayHasKey('id', $bazMetadata->fieldMappings);

        $bazMetadata->associationMappings = \array_change_key_case($bazMetadata->associationMappings, \CASE_LOWER);

        $this->assertArrayHasKey('bar', $bazMetadata->associationMappings);
        $this->assertEquals(ClassMetadataInfo::MANY_TO_ONE, $bazMetadata->associationMappings['bar']['type']);
    }

    public function testDetectManyToManyTables()
    {
        if (!$this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Platform does not support foreign keys.');
        }

        $metadatas = $this->extractClassMetadata(array("CmsUsers", "CmsGroups"));

        $this->assertArrayHasKey('CmsUsers', $metadatas, 'CmsUsers entity was not detected.');
        $this->assertArrayHasKey('CmsGroups', $metadatas, 'CmsGroups entity was not detected.');

        $this->assertEquals(2, count($metadatas['CmsUsers']->associationMappings));
        $this->assertArrayHasKey('group', $metadatas['CmsUsers']->associationMappings);
        $this->assertEquals(1, count($metadatas['CmsGroups']->associationMappings));
        $this->assertArrayHasKey('user', $metadatas['CmsGroups']->associationMappings);
    }

    public function testIgnoreManyToManyTableWithoutFurtherForeignKeyDetails()
    {
        $tableB = new \Doctrine\DBAL\Schema\Table("dbdriver_bar");
        $tableB->addColumn('id', 'integer');
        $tableB->setPrimaryKey(array('id'));

        $tableA = new \Doctrine\DBAL\Schema\Table("dbdriver_baz");
        $tableA->addColumn('id', 'integer');
        $tableA->setPrimaryKey(array('id'));

        $tableMany = new \Doctrine\DBAL\Schema\Table("dbdriver_bar_baz");
        $tableMany->addColumn('bar_id', 'integer');
        $tableMany->addColumn('baz_id', 'integer');
        $tableMany->addForeignKeyConstraint('dbdriver_bar', array('bar_id'), array('id'));

        $metadatas = $this->convertToClassMetadata(array($tableA, $tableB), array($tableMany));

        $this->assertEquals(0, count($metadatas['DbdriverBaz']->associationMappings), "no association mappings should be detected.");
    }
}
