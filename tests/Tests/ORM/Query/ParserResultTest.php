<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use LogicException;
use PHPUnit\Framework\TestCase;

class ParserResultTest extends TestCase
{
    /** @var ParserResult */
    public $parserResult;

    protected function setUp(): void
    {
        $this->parserResult = new ParserResult();
    }

    public function testGetRsm(): void
    {
        self::assertInstanceOf(ResultSetMapping::class, $this->parserResult->getResultSetMapping());
    }

    public function testItThrowsWhenAttemptingToAccessTheExecutorBeforeItIsSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Executor not set yet. Call Doctrine\ORM\Query\ParserResult::setSqlExecutor() first.',
        );

        $this->parserResult->getSqlExecutor();
    }

    public function testSetGetSqlExecutor(): void
    {
        $executor = $this->getMockForAbstractClass(AbstractSqlExecutor::class);
        $this->parserResult->setSqlExecutor($executor);
        self::assertSame($executor, $this->parserResult->getSqlExecutor());
    }

    public function testGetSqlParameterPosition(): void
    {
        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1, 2], $this->parserResult->getSqlParameterPositions(1));
    }

    public function testGetParameterMappings(): void
    {
        self::assertIsArray($this->parserResult->getParameterMappings());

        $this->parserResult->addParameterMapping(1, 1);
        $this->parserResult->addParameterMapping(1, 2);
        self::assertEquals([1 => [1, 2]], $this->parserResult->getParameterMappings());
    }
}
