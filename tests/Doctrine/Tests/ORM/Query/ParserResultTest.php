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
        self::assertInstanceOf(
            'Doctrine\ORM\Query\ResultSetMapping',
            $this->parserResult->getResultSetMapping()
        );
    }

    public function testSetGetSqlExecutor()
    {
        self::assertNull($this->parserResult->getSqlExecutor());

        $executor = $this->getMock('Doctrine\ORM\Query\Exec\AbstractSqlExecutor', array('execute'));
        $this->parserResult->setSqlExecutor($executor);
        self::assertSame($executor, $this->parserResult->getSqlExecutor());
    }

    public function testGetSqlParameterPosition()
    {
        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals(array(1, 2), $this->parserResult->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings()
    {
        self::assertInternalType('array', $this->parserResult->getParameterMappings());

        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals(array(1 => array(1, 2)), $this->parserResult->getParameterMappings());
    }
}