<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use PHPUnit\Framework\TestCase;

class ParserResultTest extends TestCase
{
    public $parserResult;

    public function setUp()
    {
        $this->parserResult = new ParserResult();
    }

    public function testGetRsm()
    {
        self::assertInstanceOf(ResultSetMapping::class, $this->parserResult->getResultSetMapping());
    }

    public function testSetGetSqlExecutor()
    {
        self::assertNull($this->parserResult->getSqlExecutor());

        $executor = $this->getMockBuilder(AbstractSqlExecutor::class)->setMethods(['execute'])->getMock();
        $this->parserResult->setSqlExecutor($executor);
        self::assertSame($executor, $this->parserResult->getSqlExecutor());
    }

    public function testGetSqlParameterPosition()
    {
        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1, 2], $this->parserResult->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings()
    {
        self::assertInternalType('array', $this->parserResult->getParameterMappings());

        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1 => [1, 2]], $this->parserResult->getParameterMappings());
    }
}
