<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

abstract class UpdateSchemaTestCase extends \Doctrine\Tests\OrmTestCase
{
    protected function _doTestAddField()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->mapField(array('fieldName' => 'street', 'type' => 'string'));

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses ADD street VARCHAR(255) NOT NULL",
            $sql[0]
        );
    }

    protected function _doTestChangeColumnName()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['columnName'] = 'the_city';

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ADD the_city VARCHAR(50) NOT NULL", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses DROP city", $sql[1]);
    }

    protected function _doTestChangeNullability()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['nullable'] = true;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city VARCHAR(50) DEFAULT NULL", $sql[0]);
    }

    /**
     * @group DDC-102
     */
    protected function _doTestChangeNullabilityToNull()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");

        $this->assertFalse($classMetadata->fieldMappings['city']['nullable']);
        unset($classMetadata->fieldMappings['city']['nullable']);

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(0, count($sql));
    }

    protected function _doTestChangeType()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['type'] = "text";

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city TINYTEXT NOT NULL", $sql[0]);
    }

    protected function _doTestChangeUniqueness()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['unique'] = true;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city VARCHAR(50) NOT NULL UNIQUE", $sql[0]);
    }

    protected function _doTestChangeLength()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['length'] = 200;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses CHANGE city city VARCHAR(200) NOT NULL', $sql[0]);
    }

    /**
     * @group DDC-101
     */
    protected function _doTestChangeLengthToNull()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['length'] = null;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses CHANGE city city VARCHAR(255) NOT NULL', $sql[0]);
    }

    protected function _doTestChangeDecimalLengthPrecision()
    {
        $this->markTestSkipped('Decimal Scale changes not supported yet, because of DDC-89.');

        $this->_initSchemaTool('DecimalModel');

        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\Generic\DecimalModel");
        $classMetadata->fieldMappings['decimal']['precision'] = 10;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(10, 2) NOT NULL', $sql[0]);
    }

    protected function _doTestChangeDecimalLengthScale()
    {
        $this->markTestSkipped('Decimal Scale changes not supported yet, because of DDC-89.');

        $this->_initSchemaTool('DecimalModel');

        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\Generic\DecimalModel");
        $classMetadata->fieldMappings['decimal']['scale'] = 3;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(5, 3) NOT NULL', $sql[0]);
    }

    protected function _doTestChangeFixed()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['fixed'] = true;

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses CHANGE city city CHAR(50) NOT NULL",
            $sql[0]
        );
    }

    protected function _doTestAddIndex()
    {
        $this->markTestSkipped('Not yet supported by SchemaTool, see DDC-90');

        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->primaryTable['indexes'] = array('searchCity' => array('columns' => array('city')));

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "CREATE INDEX searchCity (city)",
            $sql[0]
        );
    }

    protected function _doTestRemoveField()
    {
        $this->_initSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("Doctrine\Tests\Models\CMS\CmsAddress");
        unset($classMetadata->fieldMappings['city']);

        return $this->_getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses DROP city",
            $sql[0]
        );
    }

    /*
     * Utility methods start here
     */

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em = null;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    private $_schemaTool = null;

    /**
     *
     * @param  string $fixtureName
     * @return \Doctrine\ORM\Tools\SchemaTool
     */
    protected function _initSchemaTool($fixtureName)
    {
        if($this->_em == null || $this->_schemaTool == null) {
            $this->_createSchemaTool($fixtureName, $this->_createPlatform());
        }
    }

    abstract protected function _createPlatform();

    private function _createSchemaTool($fixtureName, $platform)
    {
        $fixtureFile = __DIR__."/DbFixture/".$fixtureName.".php";;
        if(!file_exists($fixtureFile)) {
            throw new \Exception("Cannot find fixture file: ".$fixtureFile);
        }
        $fixture = include $fixtureFile;

        $sm = new UpdateSchemaMock($fixture);

        $this->_em = $this->_getTestEntityManager(null, null, null, false);
        $this->_em->getConnection()->setDatabasePlatform($platform);
        $this->_em->getConnection()->getDriver()->setSchemaManager($sm);

        $this->_schemaTool = new SchemaTool($this->_em);
    }

    protected function _getUpdateSchemaSql(array $classMetadata)
    {
        if($this->_schemaTool !== null) {
            return $this->_schemaTool->getUpdateSchemaSql($classMetadata);
        } else {
            throw new \Exception("SchemaTool was not initialized.");
        }
    }

    /**
     * @param  string $className
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function _getMetadataFor($className)
    {
        if(!class_exists($className, true)) {
            throw new \Exception("Class ".$className." used for UpdateSchemaTestCase was not found!");
        }
        if($this->_em == null) {
            throw new \Exception("SchemaTool and EntityManager are not initialized.");
        }

        return $this->_em->getClassMetadata($className);
    }
}

class UpdateSchemaMock extends \Doctrine\DBAL\Schema\AbstractSchemaManager
{
    private $_fixtureData;

    public function __construct($fixtureData)
    {
        $this->_fixtureData = $fixtureData;
    }

    public function listTables()
    {
        return array_keys($this->_fixtureData);
    }

    public function listTableColumns($tableName)
    {
        return $this->_fixtureData[$tableName];
    }
}