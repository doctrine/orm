<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QuerySetMapping;
use Doctrine\ORM\Query\ResultSetMapping;
use LogicException;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

use function unserialize;

class ParserResultTest extends TestCase
{
    use VerifyDeprecations;

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

    public function testGetQsm(): void
    {
        $qsm = $this->parserResult->getQuerySetMapping();

        self::assertInstanceOf(QuerySetMapping::class, $qsm);
        self::assertTrue($qsm->isEmpty());
        self::assertSame($qsm, $this->parserResult->getQuerySetMapping());
    }

    public function testGetQsmOnAnInstanceUnserializedWithoutOne(): void
    {
        $legacy = 'O:31:"Doctrine\ORM\Query\ParserResult":1:'
            . "{s:50:\"\0Doctrine\\ORM\\Query\\ParserResult\0parameterMappings\";a:0:{}}";

        $parserResult = unserialize($legacy);

        self::assertInstanceOf(ParserResult::class, $parserResult);
        self::assertTrue($parserResult->getQuerySetMapping()->isEmpty());
    }

    #[IgnoreDeprecations]
    public function testItThrowsWhenAttemptingToAccessTheExecutorBeforeItIsSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Executor not set yet. Call Doctrine\ORM\Query\ParserResult::setSqlExecutor() first.',
        );

        $this->parserResult->getSqlExecutor();
    }

    #[IgnoreDeprecations]
    public function testSetGetSqlExecutor(): void
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/11188');

        $executor = $this->createStub(AbstractSqlExecutor::class);
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
