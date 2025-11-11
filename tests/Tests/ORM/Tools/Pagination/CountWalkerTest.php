<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

#[Group('DDC-1613')]
class CountWalkerTest extends PaginationTestCase
{
    public function testCountQuery(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT b0_.id) AS sclr_0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id',
            $query->getSQL(),
        );
    }

    public function testCountQueryWithoutDistinctUsesCountStar(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, false);

        self::assertEquals(
            'SELECT count(*) AS sclr_0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id',
            $query->getSQL(),
        );
    }

    public function testCountQueryMixedResultsWithName(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT a0_.id) AS sclr_0 FROM Author a0_',
            $query->getSQL(),
        );
    }

    public function testCountQueryKeepsGroupBy(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b GROUP BY b.id',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT b0_.id) AS sclr_0 FROM BlogPost b0_ GROUP BY b0_.id',
            $query->getSQL(),
        );
    }

    public function testCountQueryRemovesOrderBy(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a ORDER BY a.name',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT b0_.id) AS sclr_0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id',
            $query->getSQL(),
        );
    }

    public function testCountQueryRemovesLimits(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT b0_.id) AS sclr_0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id',
            $query->getSQL(),
        );
    }

    public function testCountQueryHavingException(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setFirstResult(0)->setMaxResults(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot count query that uses a HAVING clause. Use the output walkers for pagination');

        $query->getSQL();
    }

    /**
     * Arbitrary Join
     */
    public function testCountQueryWithArbitraryJoin(): void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p LEFT JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c',
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(0)->setMaxResults(null);

        self::assertEquals(
            'SELECT count(DISTINCT b0_.id) AS sclr_0 FROM BlogPost b0_ LEFT JOIN Category c1_ ON (b0_.category_id = c1_.id)',
            $query->getSQL(),
        );
    }
}
