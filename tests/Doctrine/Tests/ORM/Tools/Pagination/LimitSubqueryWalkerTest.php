<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker;

/**
 * @group DDC-1613
 */
class LimitSubqueryWalkerTest extends PaginationTestCase
{
    public function testLimitSubquery()
    {
        $dql        = 'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id"',
            $limitQuery->getSQL()
        );
    }

    public function testLimitSubqueryWithSort()
    {
        $dql        = 'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0, t0."title" AS c1 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id" ORDER BY t0."title" ASC',
            $limitQuery->getSQL()
        );
    }

    public function testCountQuery_MixedResultsWithName()
    {
        $dql        = 'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0, sum(t0."name") AS c1 FROM "Author" t0',
            $limitQuery->getSQL()
        );
    }

    /**
     * @group DDC-2890
     */
    public function testLimitSubqueryWithSortOnAssociation()
    {
        $dql        = 'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p ORDER BY p.author';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0, t0."author_id" AS c1 FROM "MyBlogPost" t0 ORDER BY t0."author_id" ASC',
            $limitQuery->getSQL()
        );
    }

    /**
     * Arbitrary Join
     */
     public function testLimitSubqueryWithArbitraryJoin()
    {
        $dql        = 'SELECT p, c FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON (t0."category_id" = t1."id")',
            $limitQuery->getSQL()
        );
    }

    public function testLimitSubqueryWithSortWithArbitraryJoin()
    {
        $dql        = 'SELECT p, c FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c ORDER BY p.title';
        $query      = $this->entityManager->createQuery($dql);
        $limitQuery = clone $query;

        $limitQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [LimitSubqueryWalker::class]);

        self::assertEquals(
            'SELECT DISTINCT t0."id" AS c0, t0."title" AS c1 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON (t0."category_id" = t1."id") ORDER BY t0."title" ASC',
            $limitQuery->getSQL()
        );
    }
}

