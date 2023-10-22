<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;
use Generator;

/** @group DDC-1613 */
class WhereInWalkerTest extends PaginationTestCase
{
    /**
     * @dataProvider exampleQueries
     */
    public function testDqlQueryTransformation(string $dql, string $expectedSql): void
    {
        $query = $this->entityManager->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $query->setHint(WhereInWalker::HINT_PAGINATOR_HAS_IDS, true);

        $result = (new Parser($query))->parse();

        self::assertEquals($expectedSql, $result->getSqlExecutor()->getSqlStatements());
        self::assertEquals([0], $result->getSqlParameterPositions(WhereInWalker::PAGINATOR_ID_ALIAS));
    }

    public static function exampleQueries(): Generator
    {
        yield 'no WHERE condition' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE u0_.id IN (?)',
        ];

        yield 'mixed results with name' => [
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a',
            'SELECT a0_.id AS id_0, a0_.name AS name_1, sum(a0_.name) AS sclr_2 FROM Author a0_ WHERE a0_.id IN (?)',
        ];

        yield 'single WHERE condition' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND u0_.id IN (?)',
        ];

        yield 'multiple WHERE conditions with AND' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND 2 = 2 AND u0_.id IN (?)',
        ];

        yield 'multiple WHERE conditions with OR' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 OR 2 = 2',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND u0_.id IN (?)',
        ];

        yield 'multiple WHERE conditions with mixed clauses 1' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE (1 = 1 OR 2 = 2) AND 3 = 3',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND 3 = 3 AND u0_.id IN (?)',
        ];

        yield 'multiple WHERE conditions with mixed clauses 2' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2 OR 3 = 3',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 AND 2 = 2 OR 3 = 3) AND u0_.id IN (?)',
        ];

        yield 'WHERE NOT condition' => [
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE NOT 1 = 2',
            'SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (NOT 1 = 2) AND u0_.id IN (?)',
        ];

        yield 'arbitary join with no WHERE' => [
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c',
            'SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ INNER JOIN Category c1_ ON (b0_.category_id = c1_.id) WHERE b0_.id IN (?)',
        ];

        yield 'arbitary join with single WHERE' => [
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c WHERE 1 = 1',
            'SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ INNER JOIN Category c1_ ON (b0_.category_id = c1_.id) WHERE 1 = 1 AND b0_.id IN (?)',
        ];
    }
}
