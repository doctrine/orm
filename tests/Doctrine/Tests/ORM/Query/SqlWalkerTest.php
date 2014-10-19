<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query;
use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Query\ParserResult;

class SqlWalkerTest extends OrmTestCase
{
    public function testGetSQLTableAlias()
    {
        $query     = new Query($this->_getTestEntityManager());
        $sqlWalker = new SqlWalker($query, new ParserResult(), array());

        $this->assertSame('t0_', $sqlWalker->getSQLTableAlias('table'));
        $this->assertSame('t1_', $sqlWalker->getSQLTableAlias('çtable'));
    }
}
