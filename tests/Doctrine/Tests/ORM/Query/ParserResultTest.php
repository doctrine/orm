<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\ParserResult;

class ParserResultTest extends \PHPUnit_Framework_TestCase
{
    public $result;

    public function setUp()
    {
        $this->result = new ParserResult();
    }

    public function testGetRsm()
    {
        $this->assertType(
            'Doctrine\ORM\Query\ResultSetMapping',
            $this->result->getResultSetMapping()
        );
    }

    public function testSetGetSqlExecutor()
    {
        $this->assertNull($this->result->getSqlExecutor());

        $executor = $this->getMock('Doctrine\ORM\Query\Exec\AbstractSqlExecutor', array('execute'));
        $this->result->setSqlExecutor($executor);
        $this->assertSame($executor, $this->result->getSqlExecutor());
    }

    public function testGetSqlParameterPosition()
    {
        $this->result->addParameterMapping(1, 1);
        $this->result->addParameterMapping(1, 2);
        $this->assertEquals(array(1, 2), $this->result->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings()
    {
        $this->assertType('array', $this->result->getParameterMappings());

        $this->result->addParameterMapping(1, 1);
        $this->result->addParameterMapping(1, 2);
        $this->assertEquals(array(1 => array(1, 2)), $this->result->getParameterMappings());
    }
}