<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;

/**
 * @group DDC-1613
 */
class WhereInWalkerTest extends PaginationTestCase
{
    public function testWhereInQuery_NoWhere()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testCountQuery_MixedResultsWithName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t0."name" AS c1, sum(t0."name") AS c2 FROM "Author" t0 WHERE t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_SingleWhere()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE 1 = 1 AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_MultipleWhereWithAnd()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE 1 = 1 AND 2 = 2 AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_MultipleWhereWithOr()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 OR 2 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE (1 = 1 OR 2 = 2) AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_MultipleWhereWithMixed_1()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE (1 = 1 OR 2 = 2) AND 3 = 3'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE (1 = 1 OR 2 = 2) AND 3 = 3 AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_MultipleWhereWithMixed_2()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2 OR 3 = 3'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE (1 = 1 AND 2 = 2 OR 3 = 3) AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQuery_WhereNot()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE NOT 1 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t1."id" AS c1 FROM "User" t0 INNER JOIN "user_group" t2 ON t0."id" = t2."user_id" INNER JOIN "groups" t1 ON t1."id" = t2."group_id" WHERE (NOT 1 = 2) AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    /**
     * Arbitrary Join
     */
    public function testWhereInQueryWithArbitraryJoin_NoWhere()
    {
        $whereInQuery  = $this->entityManager->createQuery(
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c'
        );
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t0."author_id" AS c1, t0."category_id" AS c2 FROM "BlogPost" t0 INNER JOIN "Category" t1 ON (t0."category_id" = t1."id") WHERE t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }

    public function testWhereInQueryWithArbitraryJoin_SingleWhere()
    {
        $whereInQuery = $this->entityManager->createQuery(
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c WHERE 1 = 1'
        );
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        self::assertEquals(
            'SELECT t0."id" AS c0, t0."author_id" AS c1, t0."category_id" AS c2 FROM "BlogPost" t0 INNER JOIN "Category" t1 ON (t0."category_id" = t1."id") WHERE 1 = 1 AND t0."id" IN (?)',
            $whereInQuery->getSQL()
        );
    }
}

