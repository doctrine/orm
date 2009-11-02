<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

class OracleUpdateSchemaTest extends UpdateSchemaTestCase
{
    protected function _createPlatform()
    {
        return new \Doctrine\DBAL\Platforms\OraclePlatform();
    }

    public function testAddField()
    {
        $sql = $this->_doTestAddField();

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses ADD (street VARCHAR2(255) NOT NULL)",
            $sql[0]
        );
    }

    public function testChangeColumnName()
    {
        $sql = $this->_doTestChangeColumnName();

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ADD (the_city VARCHAR2(50) NOT NULL)", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses DROP COLUMN city", $sql[1]);
    }

    public function testChangeNullability()
    {
        $sql = $this->_doTestChangeNullability();

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses MODIFY (city  VARCHAR2(50) DEFAULT NULL)", $sql[0]);
    }

    /**
     * @group DDC-102
     */
    public function testChangeNullabilityToNull()
    {
        $sql = $this->_doTestChangeNullabilityToNull();

        $this->assertEquals(0, count($sql));
    }

    public function testChangeType()
    {
        $sql = $this->_doTestChangeType();

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses MODIFY (city  CLOB NOT NULL)", $sql[0]);
    }

    public function testChangeUniqueness()
    {
        $this->markTestSkipped('Not supported on Postgres-Sql yet.');

        $sql = $this->_doTestChangeUniqueness();

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city TYPE VARCHAR(50)", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city SET NOT NULL", $sql[1]);
    }

    public function testChangeLength()
    {
        $sql = $this->_doTestChangeLength();

        $this->assertEquals(1, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses MODIFY (city  VARCHAR2(200) NOT NULL)', $sql[0]);
    }

    /**
     * @group DDC-101
     */
    public function testChangeLengthToNull()
    {
        $sql = $this->_doTestChangeLengthToNull();

        $this->assertEquals(1, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses MODIFY (city  VARCHAR2(255) NOT NULL)', $sql[0]);
    }

    public function testChangeDecimalLengthPrecision()
    {
        $sql = $this->_doTestChangeDecimalLengthPrecision();

        $this->assertEquals(2, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(10, 2) NOT NULL', $sql[0]);
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(10, 2) NOT NULL', $sql[1]);
    }

    public function testChangeDecimalLengthScale()
    {
        $sql = $this->_doTestChangeDecimalLengthScale();

        $this->assertEquals(2, count($sql));
        // invalid sql, because not escaped
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(5, 3) NOT NULL', $sql[0]);
        $this->assertEquals('ALTER TABLE decimal_model CHANGE decimal decimal NUMERIC(5, 3) NOT NULL', $sql[1]);
    }

    public function testChangeFixed()
    {
        $sql = $this->_doTestChangeFixed();

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses MODIFY (city  CHAR(50) NOT NULL)", $sql[0]);
    }

    public function testAddIndex()
    {
        $sql = $this->_doTestAddIndex();

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "CREATE INDEX searchCity (city)",
            $sql[0]
        );
    }

    public function testRemoveField()
    {
        $sql = $this->_doTestRemoveField();

        $this->assertEquals(1, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses DROP COLUMN city", $sql[0]);
    }
}