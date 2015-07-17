<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\ORM\Query;

class LimitSubqueryOutputWalkerTest extends PaginationTestCase
{
    public function testLimitSubquery()
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a');
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_0 FROM (SELECT m0_.id AS id_0, m0_.title AS title_1, c1_.id AS id_2, a2_.id AS id_3, a2_.name AS name_4, m0_.author_id AS author_id_5, m0_.category_id AS category_id_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result", $limitQuery->getSql()
        );
    }

    public function testLimitSubqueryWithSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_0, MIN(sclr_5) AS dctrn_minrownum FROM (SELECT m0_.id AS id_0, m0_.title AS title_1, c1_.id AS id_2, a2_.id AS id_3, a2_.name AS name_4, ROW_NUMBER() OVER(ORDER BY m0_.title ASC) AS sclr_5, m0_.author_id AS author_id_6, m0_.category_id AS category_id_7 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithHiddenScalarSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
           'SELECT u, g, COUNT(g.id) AS hidden g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_1, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS sclr_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY id_1 ORDER BY dctrn_minrownum ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform);

        $this->testLimitSubquery();

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }
    
    public function testLimitSubqueryWithSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID_0, MIN(SCLR_5) AS dctrn_minrownum FROM (SELECT m0_.id AS ID_0, m0_.title AS TITLE_1, c1_.id AS ID_2, a2_.id AS ID_3, a2_.name AS NAME_4, ROW_NUMBER() OVER(ORDER BY m0_.title ASC) AS SCLR_5, m0_.author_id AS AUTHOR_ID_6, m0_.category_id AS CATEGORY_ID_7 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID_1, MIN(SCLR_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC) AS SCLR_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY ID_1 ORDER BY dctrn_minrownum ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID_1, MIN(SCLR_3) AS dctrn_minrownum FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2, ROW_NUMBER() OVER(ORDER BY COUNT(g0_.id) ASC, u1_.id DESC) AS SCLR_3 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result GROUP BY ID_1 ORDER BY dctrn_minrownum ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a');
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID_0 FROM (SELECT m0_.id AS ID_0, m0_.title AS TITLE_1, c1_.id AS ID_2, a2_.id AS ID_3, a2_.name AS NAME_4, m0_.author_id AS AUTHOR_ID_5, m0_.category_id AS CATEGORY_ID_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testCountQueryMixedResultsWithName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a');
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, sum(a0_.name) AS sclr_2 FROM Author a0_) dctrn_result", $limitQuery->getSql()
        );
    }

    /**
     * @group DDC-3336
     */
    public function testCountQueryWithArithmeticOrderByCondition()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY (1 - 1000) * 1 DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1 FROM Author a0_) dctrn_result ORDER BY (1 - 1000) * 1 DESC',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItem()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.image_height AS image_height_2, a0_.image_width AS image_width_3, a0_.image_alt_desc AS image_alt_desc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result ORDER BY image_height_2 * image_width_3 DESC',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoined()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT u0_.id AS id_0, a1_.image_height AS image_height_1, a1_.image_width AS image_width_2, a1_.user_id AS user_id_3 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result ORDER BY image_height_1 * image_width_2 DESC',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemJoinedWithPartial()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, partial a.{id, image_alt_desc} FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT u0_.id AS id_0, a1_.id AS id_1, a1_.image_alt_desc AS image_alt_desc_2, a1_.image_height AS image_height_3, a1_.image_width AS image_width_4, a1_.user_id AS user_id_5 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result ORDER BY image_height_3 * image_width_4 DESC',
            $query->getSQL()
        );
    }

    public function testCountQueryWithComplexScalarOrderByItemOracle()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_height * a.image_width DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new OraclePlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT ID_0, MIN(SCLR_5) AS dctrn_minrownum FROM (SELECT a0_.id AS ID_0, a0_.image AS IMAGE_1, a0_.image_height AS IMAGE_HEIGHT_2, a0_.image_width AS IMAGE_WIDTH_3, a0_.image_alt_desc AS IMAGE_ALT_DESC_4, ROW_NUMBER() OVER(ORDER BY a0_.image_height * a0_.image_width DESC) AS SCLR_5, a0_.user_id AS USER_ID_6 FROM Avatar a0_) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * @group DDC-3434
     */
    public function testLimitSubqueryWithHiddenSelectionInOrderBy()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, a.name AS HIDDEN ord FROM Doctrine\Tests\ORM\Tools\Pagination\Author a ORDER BY ord DESC'
        );

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, a0_.name AS name_2 FROM Author a0_) dctrn_result ORDER BY name_2 DESC',
            $query->getSql()
        );
    }

    public function testLimitSubqueryWithColumnWithSortDirectionInName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a FROM Doctrine\Tests\ORM\Tools\Pagination\Avatar a ORDER BY a.image_alt_desc DESC'
        );
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertSame(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.image_height AS image_height_2, a0_.image_width AS image_width_3, a0_.image_alt_desc AS image_alt_desc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result ORDER BY image_alt_desc_4 DESC',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByInnerJoined()
    {
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b JOIN b.author a ORDER BY a.name ASC'
        );

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0 FROM (SELECT b0_.id AS id_0, a1_.name AS name_1, b0_.author_id AS author_id_2, b0_.category_id AS category_id_3 FROM BlogPost b0_ INNER JOIN Author a1_ ON b0_.author_id = a1_.id) dctrn_result ORDER BY name_1 ASC',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClauseMySql()
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0 FROM (SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ WHERE ((SELECT COUNT(b1_.id) AS dctrn__1 FROM BlogPost b1_) = 1)) dctrn_result ORDER BY id_0 DESC',
            $query->getSQL()
        );
    }

    public function testLimitSubqueryWithOrderByAndSubSelectInWhereClausePgSql()
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new PostgreSqlPlatform());
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b
WHERE  ((SELECT COUNT(simple.id) FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost simple) = 1)
ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0, MIN(sclr_1) AS dctrn_minrownum FROM (SELECT b0_.id AS id_0, ROW_NUMBER() OVER(ORDER BY b0_.id DESC) AS sclr_1, b0_.author_id AS author_id_2, b0_.category_id AS category_id_3 FROM BlogPost b0_ WHERE ((SELECT COUNT(b1_.id) AS dctrn__1 FROM BlogPost b1_) = 1)) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * This tests ordering by property that has the 'declared' field.
     */
    public function testLimitSubqueryOrderByFieldFromMappedSuperclass()
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MySqlPlatform());

        // now use the third one in query
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\Banner b ORDER BY b.id DESC'
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0 FROM (SELECT b0_.id AS id_0, b0_.name AS name_1 FROM Banner b0_) dctrn_result ORDER BY id_0 DESC',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression (mysql).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpression()
    {
        $this->entityManager->getConnection()->setDatabasePlatform(new MysqlPlatform());

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
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, (SELECT MIN(m1_.title) AS dctrn__1 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS sclr_2 FROM Author a0_) dctrn_result ORDER BY sclr_2 DESC',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (postgres).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionPg()
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
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT id_0, MIN(sclr_3) AS dctrn_minrownum FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, (SELECT MIN(m1_.title) AS dctrn__1 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS sclr_2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(m1_.title) AS dctrn__2 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) DESC) AS sclr_3 FROM Author a0_) dctrn_result GROUP BY id_0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }

    /**
     * Tests order by on a subselect expression invoking RowNumberOverFunction (oracle).
     */
    public function testLimitSubqueryOrderBySubSelectOrderByExpressionOracle()
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
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            'SELECT DISTINCT ID_0, MIN(SCLR_3) AS dctrn_minrownum FROM (SELECT a0_.id AS ID_0, a0_.name AS NAME_1, (SELECT MIN(m1_.title) AS dctrn__1 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) AS SCLR_2, ROW_NUMBER() OVER(ORDER BY (SELECT MIN(m1_.title) AS dctrn__2 FROM MyBlogPost m1_ WHERE m1_.author_id = a0_.id) DESC) AS SCLR_3 FROM Author a0_) dctrn_result GROUP BY ID_0 ORDER BY dctrn_minrownum ASC',
            $query->getSQL()
        );
    }
}

