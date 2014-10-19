<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Query\ParserResult;

/**
 * Tests for {@see \Doctrine\ORM\Query\SqlWalker}
 *
 * @covers \Doctrine\ORM\Query\SqlWalker
 */
class SqlWalkerTest extends OrmTestCase
{
    /**
     * @dataProvider getColumnNamesAndSqlAliases
     */
    public function testGetSQLTableAlias($tableName, $expectedAlias)
    {
        $query     = new Query($this->_getTestEntityManager());
        $sqlWalker = new SqlWalker($query, new ParserResult(), array());

        $this->assertSame($expectedAlias, $sqlWalker->getSQLTableAlias($tableName));
    }

    /**
     * @dataProvider getColumnNamesAndSqlAliases
     */
    public function testGetSQLTableAliasIsSameForMultipleCalls($tableName)
    {
        $query     = new Query($this->_getTestEntityManager());
        $sqlWalker = new SqlWalker($query, new ParserResult(), array());

        $this->assertSame($sqlWalker->getSQLTableAlias($tableName), $sqlWalker->getSQLTableAlias($tableName));
    }

    /**
     * @private data provider
     *
     * @return string[][]
     */
    public function getColumnNamesAndSqlAliases()
    {
        return array(
            array('aaaaa', 'a0_'),
            array('table', 't0_'),
            array('çtable', 't0_'),
        );
    }
}
