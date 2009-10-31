<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

class MysqlUpdateSchemaTest extends UpdateSchemaTestCase
{
    protected function _createPlatform()
    {
        return new \Doctrine\DBAL\Platforms\MySqlPlatform();
    }

    public function testAddField()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $md = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $md->mapField(array('fieldName' => 'street', 'type' => 'string'));

        $sql = $st->getUpdateSchemaSql(array($md));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses ADD street VARCHAR(255) DEFAULT NULL",
            $sql[0]
        );
    }

    public function testChangeColumnName()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['columnName'] = 'the_city';

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ADD the_city VARCHAR(50) NOT NULL", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses DROP city", $sql[1]);
    }

    public function testChangeNullability()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['nullable'] = true;

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city VARCHAR(50) DEFAULT NULL", $sql[0]);
    }

    public function testChangeType()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['type'] = "text";

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city TINYTEXT NOT NULL", $sql[0]);
    }

    public function testChangeUniqueness()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['unique'] = true;

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses CHANGE city city VARCHAR(50) NOT NULL UNIQUE", $sql[0]);
    }

    public function testChangeLength()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $classMetadata->fieldMappings['city']['length'] = 200;

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses CHANGE city city VARCHAR(200) NOT NULL', $sql[0]);
    }

    public function testChangeDecimalLengthPrecision()
    {
        $this->markTestSkipped('Decimal Scale changes not supported yet, because of DDC-89.');

        $decimalModel = new \Doctrine\Tests\Models\Generic\DecimalModel();

        $st = $this->_getSchemaTool('DecimalModel');

        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\Generic\DecimalModel");
        $classMetadata->fieldMappings['decimal']['precision'] = 10;

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(10, 2) NOT NULL', $sql[0]);
    }

    public function testChangeDecimalLengthScale()
    {
        $this->markTestSkipped('Decimal Scale changes not supported yet, because of DDC-89.');

        $decimalModel = new \Doctrine\Tests\Models\Generic\DecimalModel();

        $st = $this->_getSchemaTool('DecimalModel');

        $classMetadata = $this->_getMetadataFor("\Doctrine\Tests\Models\Generic\DecimalModel");
        $classMetadata->fieldMappings['decimal']['scale'] = 3;

        $sql = $st->getUpdateSchemaSql(array($classMetadata));

        $this->assertEquals(1, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(5, 3) NOT NULL', $sql[0]);
    }

    public function testChangeFixed()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $md = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $md->fieldMappings['city']['fixed'] = true;

        $sql = $st->getUpdateSchemaSql(array($md));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses CHANGE city city CHAR(50) NOT NULL",
            $sql[0]
        );
    }

    public function testAddIndex()
    {
        $this->markTestSkipped('Not yet supported by SchemaTool, see DDC-90');

        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $md = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        $md->primaryTable['indexes'] = array('searchCity' => array('columns' => array('city')));

        $sql = $st->getUpdateSchemaSql(array($md));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "CREATE INDEX searchCity (city)",
            $sql[0]
        );
    }

    public function testRemoveField()
    {
        $address = new \Doctrine\Tests\Models\CMS\CmsAddress;

        $st = $this->_getSchemaTool("Cms");
        $md = $this->_getMetadataFor("\Doctrine\Tests\Models\CMS\CmsAddress");
        unset($md->fieldMappings['city']);

        $sql = $st->getUpdateSchemaSql(array($md));

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses DROP city",
            $sql[0]
        );
    }
}