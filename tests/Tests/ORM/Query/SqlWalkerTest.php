<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for {@see \Doctrine\ORM\Query\SqlWalker}
 */
#[CoversClass(SqlWalker::class)]
class SqlWalkerTest extends OrmTestCase
{
    private SqlWalker $sqlWalker;

    protected function setUp(): void
    {
        $this->sqlWalker = new SqlWalker(new Query($this->getTestEntityManager()), new ParserResult(), []);
    }

    #[DataProvider('getColumnNamesAndSqlAliases')]
    public function testGetSQLTableAlias($tableName, $expectedAlias): void
    {
        self::assertSame($expectedAlias, $this->sqlWalker->getSQLTableAlias($tableName));
    }

    #[DataProvider('getColumnNamesAndSqlAliases')]
    public function testGetSQLTableAliasIsSameForMultipleCalls($tableName): void
    {
        self::assertSame(
            $this->sqlWalker->getSQLTableAlias($tableName),
            $this->sqlWalker->getSQLTableAlias($tableName),
        );
    }

    /**
     * @return string[][]
     *
     * @private data provider
     */
    public static function getColumnNamesAndSqlAliases(): array
    {
        return [
            ['aaaaa', 'a0_'],
            ['table', 't0_'],
            ['çtable', 't0_'],
        ];
    }
}
