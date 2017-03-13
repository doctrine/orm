<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;

/**
 * @group DDC-1613
 */
class CountWalkerTest extends PaginationTestCase
{
    /**
     * @dataProvider provideDataForCountQuery
     */
    public function testCountQuery($dql, $sql)
    {
        $query = $this->entityManager->createQuery($dql);

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);

        $query->setFirstResult(null)->setMaxResults(null);

        self::assertEquals($sql, $query->getSQL());
    }

    public function provideDataForCountQuery()
    {
        return [
            // Multiple results and joins
            [
                'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a',
                'SELECT count(DISTINCT t0."id") AS c0 FROM "BlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id"'
            ],
            // Mixed results with name
            [
                'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a',
                'SELECT count(DISTINCT t0."id") AS c0 FROM "Author" t0'
            ],
            // Keeps group by
            [
                'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b GROUP BY b.id',
                'SELECT count(DISTINCT t0."id") AS c0 FROM "BlogPost" t0 GROUP BY t0."id"'
            ],
            // Removes order by
            [
                'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a ORDER BY a.name',
                'SELECT count(DISTINCT t0."id") AS c0 FROM "BlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id"'
            ],
            // Arbitrary join
            [
                'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p LEFT JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c',
                'SELECT count(DISTINCT t0."id") AS c0 FROM "BlogPost" t0 LEFT JOIN "Category" t1 ON (t0."category_id" = t1."id")'
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot count query that uses a HAVING clause. Use the output walkers for pagination
     */
    public function testCountQuery_HavingException()
    {
        $query = $this->entityManager->createQuery(
            'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0'
        );

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setFirstResult(null)->setMaxResults(null);

        $query->getSQL();
    }
}

