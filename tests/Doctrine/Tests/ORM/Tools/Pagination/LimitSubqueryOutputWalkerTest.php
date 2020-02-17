<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker;

final class LimitSubqueryOutputWalkerTest extends PaginationTestCase
{
    public function testLimitSubquery() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT t0."id" AS c0, t0."title" AS c1, t1."id" AS c2, t2."id" AS c3, t2."name" AS c4, t0."author_id" AS c5, t0."category_id" AS c6 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id") dctrn_result',
            $limitQuery->getSQL()
        );
    }

    public function testLimitSubqueryWithSortPg() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $query      = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0, MIN(c5) AS dctrn_minrownum FROM (SELECT t0."id" AS c0, t0."title" AS c1, t1."id" AS c2, t2."id" AS c3, t2."name" AS c4, ROW_NUMBER() OVER(ORDER BY t0."title" ASC) AS c5, t0."author_id" AS c6, t0."category_id" AS c7 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id") dctrn_result GROUP BY c0 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortPg() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $query      = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c1, MIN(c3) AS dctrn_minrownum FROM (SELECT COUNT(t0."id") AS c0, t1."id" AS c1, t0."id" AS c2, ROW_NUMBER() OVER(ORDER BY COUNT(t0."id") ASC) AS c3 FROM "User" t1 INNER JOIN "user_group" t2 ON t1."id" = t2."user_id" INNER JOIN "groups" t0 ON t0."id" = t2."group_id") dctrn_result GROUP BY c1 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortPg() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $query      = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c1, MIN(c3) AS dctrn_minrownum FROM (SELECT COUNT(t0."id") AS c0, t1."id" AS c1, t0."id" AS c2, ROW_NUMBER() OVER(ORDER BY COUNT(t0."id") ASC, t1."id" DESC) AS c3 FROM "User" t1 INNER JOIN "user_group" t2 ON t1."id" = t2."user_id" INNER JOIN "groups" t0 ON t0."id" = t2."group_id") dctrn_result GROUP BY c1 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithHiddenScalarSortPg() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $query      = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS hidden g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c1, MIN(c3) AS dctrn_minrownum FROM (SELECT COUNT(t0."id") AS c0, t1."id" AS c1, t0."id" AS c2, ROW_NUMBER() OVER(ORDER BY COUNT(t0."id") ASC, t1."id" DESC) AS c3 FROM "User" t1 INNER JOIN "user_group" t2 ON t1."id" = t2."user_id" INNER JOIN "groups" t0 ON t0."id" = t2."group_id") dctrn_result GROUP BY c1 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryPg() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $this->testLimitSubquery();

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithSortOracle() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C0, MIN(C5) AS dctrn_minrownum FROM (SELECT t0."id" AS C0, t0."title" AS C1, t1."id" AS C2, t2."id" AS C3, t2."name" AS C4, ROW_NUMBER() OVER(ORDER BY t0."title" ASC) AS C5, t0."author_id" AS C6, t0."category_id" AS C7 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id") dctrn_result GROUP BY C0 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortOracle() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C1, MIN(C3) AS dctrn_minrownum FROM (SELECT COUNT(t0."id") AS C0, t1."id" AS C1, t0."id" AS C2, ROW_NUMBER() OVER(ORDER BY COUNT(t0."id") ASC) AS C3 FROM "User" t1 INNER JOIN "user_group" t2 ON t1."id" = t2."user_id" INNER JOIN "groups" t0 ON t0."id" = t2."group_id") dctrn_result GROUP BY C1 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortOracle() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C1, MIN(C3) AS dctrn_minrownum FROM (SELECT COUNT(t0."id") AS C0, t1."id" AS C1, t0."id" AS C2, ROW_NUMBER() OVER(ORDER BY COUNT(t0."id") ASC, t1."id" DESC) AS C3 FROM "User" t1 INNER JOIN "user_group" t2 ON t1."id" = t2."user_id" INNER JOIN "groups" t0 ON t0."id" = t2."group_id") dctrn_result GROUP BY C1 ORDER BY dctrn_minrownum ASC',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryOracle() : void
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C0 FROM (SELECT t0."id" AS C0, t0."title" AS C1, t1."id" AS C2, t2."id" AS C3, t2."name" AS C4, t0."author_id" AS C5, t0."category_id" AS C6 FROM "MyBlogPost" t0 INNER JOIN "Category" t1 ON t0."category_id" = t1."id" INNER JOIN "Author" t2 ON t0."author_id" = t2."id") dctrn_result',
            $limitQuery->getSQL()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testCountQueryMixedResultsWithName() : void
    {
        $query      = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT t0."id" AS c0, t0."name" AS c1, sum(t0."name") AS c2 FROM "Author" t0) dctrn_result',
            $limitQuery->getSQL()
        );
    }

    /**
     * @group DDC-3336
     */
    public function testCountQueryWithArithmeticOrderByCondition() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY (1 - 1000) * 1 DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, (1 - 1000) * 1 FROM (SELECT t0.`id` AS c0, t0.`name` AS c1 FROM `Author` t0) dctrn_result_inner ORDER BY (1 - 1000) * 1 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItem() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c2 * c3 FROM (SELECT t0.`id` AS c0, t0.`image` AS c1, t0.`image_height` AS c2, t0.`image_width` AS c3, t0.`image_alt_desc` AS c4, t0.`user_id` AS c5 FROM `Avatar` t0) dctrn_result_inner ORDER BY c2 * c3 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoined() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT u FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c1 * c2 FROM (SELECT t0.`id` AS c0, t1.`image_height` AS c1, t1.`image_width` AS c2, t1.`user_id` AS c3 FROM `User` t0 INNER JOIN `Avatar` t1 ON t0.`id` = t1.`user_id`) dctrn_result_inner ORDER BY c1 * c2 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoinedWithPartial() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, partial a.{id, image_alt_desc} FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c3 * c4 FROM (SELECT t0.`id` AS c0, t1.`id` AS c1, t1.`image_alt_desc` AS c2, t1.`image_height` AS c3, t1.`image_width` AS c4, t1.`user_id` AS c5 FROM `User` t0 INNER JOIN `Avatar` t1 ON t0.`id` = t1.`user_id`) dctrn_result_inner ORDER BY c3 * c4 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemOracle() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C0, MIN(C5) AS dctrn_minrownum FROM (SELECT t0."id" AS C0, t0."image" AS C1, t0."image_height" AS C2, t0."image_width" AS C3, t0."image_alt_desc" AS C4, ROW_NUMBER() OVER(ORDER BY t0."image_height" * t0."image_width" DESC) AS C5, t0."user_id" AS C6 FROM "Avatar" t0) dctrn_result GROUP BY C0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * @group DDC-3434
     */
    public function testLimitSubqueryWithHiddenSelectionInOrderBy() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, a.name AS HIDDEN ord FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY ord DESC'
        );

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c2 FROM (SELECT t0."id" AS c0, t0."name" AS c1, t0."name" AS c2 FROM "Author" t0) dctrn_result_inner ORDER BY c2 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithColumnWithSortDirectionInNameMySql() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_alt_desc DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c4 FROM (SELECT t0.`id` AS c0, t0.`image` AS c1, t0.`image_height` AS c2, t0.`image_width` AS c3, t0.`image_alt_desc` AS c4, t0.`user_id` AS c5 FROM `Avatar` t0) dctrn_result_inner ORDER BY c4 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByInnerJoined() : void
    {
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b JOIN b.author a ORDER BY a.name ASC'
        );

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c1 FROM (SELECT t0."id" AS c0, t1."name" AS c1, t0."author_id" AS c2, t0."category_id" AS c3 FROM "BlogPost" t0 INNER JOIN "Author" t1 ON t0."author_id" = t1."id") dctrn_result_inner ORDER BY c1 ASC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClauseMySql() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0 FROM (SELECT t0.`id` AS c0, t0.`author_id` AS c1, t0.`category_id` AS c2 FROM `BlogPost` t0 WHERE ((SELECT COUNT(t1.`id`) AS c3 FROM `BlogPost` t1) = 1)) dctrn_result_inner ORDER BY c0 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClausePgSql() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0, MIN(c1) AS dctrn_minrownum FROM (SELECT t0."id" AS c0, ROW_NUMBER() OVER(ORDER BY t0."id" DESC) AS c1, t0."author_id" AS c2, t0."category_id" AS c3 FROM "BlogPost" t0 WHERE ((SELECT COUNT(t1."id") AS c4 FROM "BlogPost" t1) = 1)) dctrn_result GROUP BY c0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * This tests ordering by property that has the 'declared' field.
     */
    public function testLimitSubqueryOrderByFieldFromMappedSuperclassMySql() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        // now use the third one in query
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\Banner b ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0 FROM (SELECT t0.`id` AS c0, t0.`name` AS c1 FROM `Banner` t0) dctrn_result_inner ORDER BY c0 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression (mysql).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionMySql() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query = $this->entityManager->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0 FROM (SELECT DISTINCT c0, c2 FROM (SELECT t0.`id` AS c0, t0.`name` AS c1, (SELECT MIN(t1.`title`) AS c3 FROM `MyBlogPost` t1 WHERE t1.`author_id` = t0.`id`) AS c2 FROM `Author` t0) dctrn_result_inner ORDER BY c2 DESC) dctrn_result',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (postgres).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionPgSql() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());

        $query = $this->entityManager->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT c0, MIN(c4) AS dctrn_minrownum FROM (SELECT t0."id" AS c0, t0."name" AS c1, (SELECT MIN(t1."title") AS c3 FROM "MyBlogPost" t1 WHERE t1."author_id" = t0."id") AS c2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(t1."title") AS c5 FROM "MyBlogPost" t1 WHERE t1."author_id" = t0."id") DESC) AS c4 FROM "Author" t0) dctrn_result GROUP BY c0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (oracle).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionOracle() : void
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query = $this->entityManager->createQuery(
            'SELECT a,
                (
                    SELECT MIN(bp.title)
                    FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost bp
                    WHERE bp.author = a
                ) AS HIDDEN first_blog_post
            FROM Doctrine\Tests\ORM\Tools\Pagination\Author a
            ORDER BY first_blog_post DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);

        self::assertSame(
            'SELECT DISTINCT C0, MIN(C4) AS dctrn_minrownum FROM (SELECT t0."id" AS C0, t0."name" AS C1, (SELECT MIN(t1."title") AS C3 FROM "MyBlogPost" t1 WHERE t1."author_id" = t0."id") AS C2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(t1."title") AS C5 FROM "MyBlogPost" t1 WHERE t1."author_id" = t0."id") DESC) AS C4 FROM "Author" t0) dctrn_result GROUP BY C0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }
}
