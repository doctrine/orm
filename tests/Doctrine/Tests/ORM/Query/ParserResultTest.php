<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\ParserResult;

class ParserResultTest extends \PHPUnit_Framework_TestCase
{
    public $parserResult;

    public function setUp()
    {
        $this->parserResult = new ParserResult();
    }

    public function testGetRsm()
    {
        $this->assertInstanceOf(
            'Doctrine\ORM\Query\ResultSetMapping',
            $this->parserResult->getResultSetMapping()
        );
    }

    public function testSetGetSqlExecutor()
    {
        $this->assertNull($this->parserResult->getSqlExecutor());

        $executor = $this->getMock('Doctrine\ORM\Query\Exec\AbstractSqlExecutor', array('execute'));
        $this->parserResult->setSqlExecutor($executor);
        $this->assertSame($executor, $this->parserResult->getSqlExecutor());
    }

    public function testGetSqlParameterPosition()
    {
        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        $this->assertEquals(array(1, 2), $this->parserResult->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings()
    {
        $this->assertInternalType('array', $this->parserResult->getParameterMappings());

        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        $this->assertEquals(array(1 => array(1, 2)), $this->parserResult->getParameterMappings());
    }
}