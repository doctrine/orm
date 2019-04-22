<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\DoctrineTestCase;

class ParserResultTest extends DoctrineTestCase
{
    public $parserResult;

    public function setUp() : void
    {
        $this->parserResult = new ParserResult();
    }

    public function testGetRsm() : void
    {
        self::assertInstanceOf(ResultSetMapping::class, $this->parserResult->getResultSetMapping());
    }

    public function testSetGetSqlExecutor() : void
    {
        self::assertNull($this->parserResult->getSqlExecutor());

        $executor = $this->getMockBuilder(AbstractSqlExecutor::class)->setMethods(['execute'])->getMock();
        $this->parserResult->setSqlExecutor($executor);
        self::assertSame($executor, $this->parserResult->getSqlExecutor());
    }

    public function testGetSqlParameterPosition() : void
    {
        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1, 2], $this->parserResult->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings() : void
    {
        self::assertIsArray($this->parserResult->getParameterMappings());

        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1 => [1, 2]], $this->parserResult->getParameterMappings());
    }
}
