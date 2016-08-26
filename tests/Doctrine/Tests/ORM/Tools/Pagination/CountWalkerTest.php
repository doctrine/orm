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
                'SELECT count(DISTINCT b0_."id") AS sclr_0 FROM "BlogPost" b0_ INNER JOIN "Category" c1_ ON b0_."category_id" = c1_."id" INNER JOIN "Author" a2_ ON b0_."author_id" = a2_."id"'
            ],
            // Mixed results with name
            [
                'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a',
                'SELECT count(DISTINCT a0_."id") AS sclr_0 FROM "Author" a0_'
            ],
            // Keeps group by
            [
                'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b GROUP BY b.id',
                'SELECT count(DISTINCT b0_."id") AS sclr_0 FROM "BlogPost" b0_ GROUP BY b0_."id"'
            ],
            // Removes order by
            [
                'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a ORDER BY a.name',
                'SELECT count(DISTINCT b0_."id") AS sclr_0 FROM "BlogPost" b0_ INNER JOIN "Category" c1_ ON b0_."category_id" = c1_."id" INNER JOIN "Author" a2_ ON b0_."author_id" = a2_."id"'
            ],
            // Arbitrary join
            [
                'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p LEFT JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c',
                'SELECT count(DISTINCT b0_."id") AS sclr_0 FROM "BlogPost" b0_ LEFT JOIN "Category" c1_ ON (b0_."category_id" = c1_."id")'
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

