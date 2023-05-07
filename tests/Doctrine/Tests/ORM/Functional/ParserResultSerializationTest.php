<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use ReflectionMethod;

use function file_get_contents;
use function serialize;
use function unserialize;

class ParserResultSerializationTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();
    }

    public function testSerializeParserResult(): void
    {
        $query = $this->_em
            ->createQuery('SELECT u FROM Doctrine\Tests\Models\Company\CompanyEmployee u WHERE u.name = :name');

        $parserResult = self::parseQuery($query);
        $serialized   = serialize($parserResult);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertInstanceOf(SingleSelectExecutor::class, $unserialized->getSqlExecutor());
    }

    /**
     * @dataProvider provideSerializedSingleSelectResults
     */
    public function testUnserializeSingleSelectResult(string $serialized): void
    {
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ParserResult::class, $unserialized);
        $this->assertInstanceOf(ResultSetMapping::class, $unserialized->getResultSetMapping());
        $this->assertEquals(['name' => [0]], $unserialized->getParameterMappings());
        $this->assertInstanceOf(SingleSelectExecutor::class, $unserialized->getSqlExecutor());
    }

    /** @return Generator<string, array{string}> */
    public static function provideSerializedSingleSelectResults(): Generator
    {
        yield '2.14.3' => [file_get_contents(__DIR__ . '/ParserResults/single_select_2_14_3.txt')];
        yield '2.15.0' => [file_get_contents(__DIR__ . '/ParserResults/single_select_2_15_0.txt')];
    }

    private static function parseQuery(Query $query): ParserResult
    {
        $r = new ReflectionMethod($query, 'parse');
        $r->setAccessible(true);

        return $r->invoke($query);
    }
}
