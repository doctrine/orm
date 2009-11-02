<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../../../TestInit.php';

class PostgresUpdateSchemaTest extends UpdateSchemaTestCase
{
    protected function _createPlatform()
    {
        return new \Doctrine\DBAL\Platforms\PostgreSqlPlatform();
    }

    public function testAddField()
    {
        $sql = $this->_doTestAddField();

        $this->assertEquals(1, count($sql));
        $this->assertEquals(
            "ALTER TABLE cms_addresses ADD street VARCHAR(255) NOT NULL",
            $sql[0]
        );
    }

    public function testChangeColumnName()
    {
        $sql = $this->_doTestChangeColumnName();

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ADD the_city VARCHAR(50) NOT NULL", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses DROP city", $sql[1]);
    }

    public function testChangeNullability()
    {
        $sql = $this->_doTestChangeNullability();

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city TYPE VARCHAR(50)", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city DROP NOT NULL", $sql[1]);
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

        $this->assertEquals(2, count($sql));
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city TYPE TEXT", $sql[0]);
        $this->assertEquals("ALTER TABLE cms_addresses ALTER city SET NOT NULL", $sql[1]);
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

        $this->assertEquals(2, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses ALTER city TYPE VARCHAR(200)', $sql[0]);
        $this->assertEquals('ALTER TABLE cms_addresses ALTER city SET NOT NULL', $sql[1]);
    }

    /**
     * @group DDC-101
     */
    public function testChangeLengthToNull()
    {
        $sql = $this->_doTestChangeLengthToNull();

        $this->assertEquals(2, count($sql));
        $this->assertEquals('ALTER TABLE cms_addresses ALTER city TYPE VARCHAR(255)', $sql[0]);
        $this->assertEquals('ALTER TABLE cms_addresses ALTER city SET NOT NULL', $sql[1]);
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
        $this->assertEquals(
            "ALTER TABLE cms_addresses CHANGE city city CHAR(50) NOT NULL",
            $sql[0]
        );
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
        $this->assertEquals(
            "ALTER TABLE cms_addresses DROP city",
            $sql[0]
        );
    }
}