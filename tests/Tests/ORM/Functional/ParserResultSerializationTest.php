<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Closure;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\FinalizedSelectExecutor;
use Doctrine\ORM\Query\Exec\PreparedExecutorFinalizer;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use ReflectionMethod;
use Symfony\Component\VarExporter\Instantiator;
use Symfony\Component\VarExporter\VarExporter;

use function file_get_contents;
use function rtrim;
use function serialize;
use function unserialize;

class ParserResultSerializationTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();
    }

    /** @param Closure(ParserResult): ParserResult $toSerializedAndBack */
    #[DataProvider('provideToSerializedAndBack')]
    #[WithoutErrorHandler]
    public function testSerializeParserResultForQueryWithSqlWalker(Closure $toSerializedAndBack): void
    {
        $query = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\Company\CompanyEmployee u WHERE u.name = :name');

        // Use the (legacy) SqlWalker which directly puts an SqlExecutor instance into the parser result
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, Query\SqlWalker::class);

        $parserResult = self::parseQuery($query);
        $unserialized = $toSerializedAndBack($parserResult);

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertNotNull($unserialized->prepareSqlExecutor($query));
    }

    /** @param Closure(ParserResult): ParserResult $toSerializedAndBack */
    #[DataProvider('provideToSerializedAndBack')]
    public function testSerializeParserResultForQueryWithSqlOutputWalker(Closure $toSerializedAndBack): void
    {
        $query = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\Company\CompanyEmployee u WHERE u.name = :name');

        $parserResult = self::parseQuery($query);
        $unserialized = $toSerializedAndBack($parserResult);

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertNotNull($unserialized->prepareSqlExecutor($query));
    }

    /** @return Generator<string, array{Closure(ParserResult): ParserResult}> */
    public static function provideToSerializedAndBack(): Generator
    {
        yield 'native serialization function' => [
            static function (ParserResult $parserResult): ParserResult {
                return unserialize(serialize($parserResult));
            },
        ];

        $instantiatorMethod = new ReflectionMethod(Instantiator::class, 'instantiate');
        if ($instantiatorMethod->getReturnType() === null) {
            self::markTestSkipped('symfony/var-exporter 5.4+ is required.');
        }

        yield 'symfony/var-exporter' => [
            static function (ParserResult $parserResult): ParserResult {
                return eval('return ' . VarExporter::export($parserResult) . ';');
            },
        ];
    }

    #[DataProvider('provideSerializedSingleSelectResults')]
    public function testUnserializeSingleSelectResult(string $serialized): void
    {
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertInstanceOf(SingleSelectExecutor::class, $unserialized->getSqlExecutor());
        $this->assertIsString($unserialized->getSqlExecutor()->getSqlStatements());
    }

    /** @return Generator<string, array{string}> */
    public static function provideSerializedSingleSelectResults(): Generator
    {
        yield '2.17.0' => [rtrim(file_get_contents(__DIR__ . '/ParserResults/single_select_2_17_0.txt'), "\n")];
    }

    public function testSymfony44ProvidedData(): void
    {
        $sqlExecutor      = new FinalizedSelectExecutor('test');
        $sqlFinalizer     = new PreparedExecutorFinalizer($sqlExecutor);
        $resultSetMapping = $this->createMock(ResultSetMapping::class);

        $parserResult = new ParserResult();
        $parserResult->setSqlFinalizer($sqlFinalizer);
        $parserResult->setResultSetMapping($resultSetMapping);
        $parserResult->addParameterMapping('name', 0);

        $exported     = VarExporter::export($parserResult);
        $unserialized = eval('return ' . $exported . ';');

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertEquals($sqlExecutor, $unserialized->prepareSqlExecutor($this->createMock(Query::class)));
    }

    private static function parseQuery(Query $query): ParserResult
    {
        $r = new ReflectionMethod($query, 'parse');
        $r->setAccessible(true);

        return $r->invoke($query);
    }
}
