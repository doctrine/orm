<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountOutputWalker;

class CountOutputWalkerTest extends PaginationTestCase
{
    /**
     * @dataProvider provideDataForCountQuery
     */
    public function testCountQuery($dql, $sql)
    {
        $query = $this->entityManager->createQuery($dql);

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, CountOutputWalker::class);
        $query->setFirstResult(null)->setMaxResults(null);

        self::assertSame($sql, $query->getSQL());
    }

    public function provideDataForCountQuery()
    {
        return [
            // Multiple results and joins
            [
                'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a',
                'SELECT COUNT(*) AS dctrn_count FROM (SELECT DISTINCT c0 FROM (SELECT t0."id" AS c0, t1."id" AS c1, t2."id" AS c2, t2."name" AS c3, t0."author_id" AS c4, t0."category_id" AS c5 FROM "BlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id") dctrn_result) dctrn_table',
            ],
            // Mixed results with name
            [
                'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a',
                'SELECT COUNT(*) AS dctrn_count FROM (SELECT DISTINCT c0 FROM (SELECT t0."id" AS c0, t0."name" AS c1, sum(t0."name") AS c2 FROM "Author" t0) dctrn_result) dctrn_table',
            ],
            // Grouping support
            [
                'SELECT p.name FROM Doctrine\Tests\ORM\Tools\Pagination\Person p GROUP BY p.name',
                'SELECT COUNT(*) AS dctrn_count FROM (SELECT t0."name" AS c0 FROM "Person" t0 GROUP BY t0."name") dctrn_table',
            ],
            // Having support
            [
                'SELECT g, u, count(u.id) AS userCount FROM Doctrine\Tests\ORM\Tools\Pagination\Group g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0',
                'SELECT COUNT(*) AS dctrn_count FROM (SELECT count(t0."id") AS c0, t1."id" AS c1, t0."id" AS c2 FROM "groups" t1 LEFT JOIN "user_group" t2 ON t1."id" = t2."group_id" LEFT JOIN "User" t0 ON t0."id" = t2."user_id" GROUP BY t1."id" HAVING c0 > 0) dctrn_table',
            ],
        ];
    }

    public function testCountQueryOrderBySqlServer()
    {
        if ($this->entityManager->getConnection()->getDatabasePlatform()->getName() !== 'mssql') {
            $this->markTestSkipped('SQLServer only test.');
        }

        $this->testCountQuery(
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p ORDER BY p.id',
            'SELECT COUNT(*) AS dctrn_count FROM (SELECT DISTINCT c0 FROM (SELECT t0.[id] AS c0, t0.[author_id] AS c1, t0.[category_id] AS c2 FROM [BlogPost] t0) dctrn_result) dctrn_table'
        );
    }
}
