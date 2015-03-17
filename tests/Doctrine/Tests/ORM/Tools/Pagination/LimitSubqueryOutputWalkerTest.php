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
            "SELECT DISTINCT id_0, title_1 FROM (SELECT m0_.id AS id_0, m0_.title AS title_1, c1_.id AS id_2, a2_.id AS id_3, a2_.name AS name_4, m0_.author_id AS author_id_5, m0_.category_id AS category_id_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result ORDER BY title_1 ASC", $limitQuery->getSql()
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
            "SELECT DISTINCT id_1, sclr_0 FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result ORDER BY sclr_0 ASC",
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
            "SELECT DISTINCT id_1, sclr_0 FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result ORDER BY sclr_0 ASC, id_1 DESC",
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
            "SELECT DISTINCT id_1, sclr_0 FROM (SELECT COUNT(g0_.id) AS sclr_0, u1_.id AS id_1, g0_.id AS id_2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result ORDER BY sclr_0 ASC, id_1 DESC",
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
            "SELECT DISTINCT ID_0, TITLE_1 FROM (SELECT m0_.id AS ID_0, m0_.title AS TITLE_1, c1_.id AS ID_2, a2_.id AS ID_3, a2_.name AS NAME_4, m0_.author_id AS AUTHOR_ID_5, m0_.category_id AS CATEGORY_ID_6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result ORDER BY TITLE_1 ASC", $limitQuery->getSql()
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
            "SELECT DISTINCT ID_1, SCLR_0 FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result ORDER BY SCLR_0 ASC",
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
            "SELECT DISTINCT ID_1, SCLR_0 FROM (SELECT COUNT(g0_.id) AS SCLR_0, u1_.id AS ID_1, g0_.id AS ID_2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id) dctrn_result ORDER BY SCLR_0 ASC, ID_1 DESC",
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
            'SELECT DISTINCT id_0, (1 - 1000) * 1 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1 FROM Author a0_) dctrn_result ORDER BY (1 - 1000) * 1 DESC',
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
            'SELECT DISTINCT id_0, image_height_2 * image_width_3 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.image_height AS image_height_2, a0_.image_width AS image_width_3, a0_.image_alt_desc AS image_alt_desc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result ORDER BY image_height_2 * image_width_3 DESC',
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
            'SELECT DISTINCT id_0, image_height_1 * image_width_2 FROM (SELECT u0_.id AS id_0, a1_.image_height AS image_height_1, a1_.image_width AS image_width_2, a1_.user_id AS user_id_3 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result ORDER BY image_height_1 * image_width_2 DESC',
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
            'SELECT DISTINCT id_0, image_height_3 * image_width_4 FROM (SELECT u0_.id AS id_0, a1_.id AS id_1, a1_.image_alt_desc AS image_alt_desc_2, a1_.image_height AS image_height_3, a1_.image_width AS image_width_4, a1_.user_id AS user_id_5 FROM User u0_ INNER JOIN Avatar a1_ ON u0_.id = a1_.user_id) dctrn_result ORDER BY image_height_3 * image_width_4 DESC',
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
            'SELECT DISTINCT ID_0, IMAGE_HEIGHT_2 * IMAGE_WIDTH_3 FROM (SELECT a0_.id AS ID_0, a0_.image AS IMAGE_1, a0_.image_height AS IMAGE_HEIGHT_2, a0_.image_width AS IMAGE_WIDTH_3, a0_.image_alt_desc AS IMAGE_ALT_DESC_4, a0_.user_id AS USER_ID_5 FROM Avatar a0_) dctrn_result ORDER BY IMAGE_HEIGHT_2 * IMAGE_WIDTH_3 DESC',
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
            'SELECT DISTINCT id_0, name_2 FROM (SELECT a0_.id AS id_0, a0_.name AS name_1, a0_.name AS name_2 FROM Author a0_) dctrn_result ORDER BY name_2 DESC',
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
            'SELECT DISTINCT id_0, image_alt_desc_4 FROM (SELECT a0_.id AS id_0, a0_.image AS image_1, a0_.image_height AS image_height_2, a0_.image_width AS image_width_3, a0_.image_alt_desc AS image_alt_desc_4, a0_.user_id AS user_id_5 FROM Avatar a0_) dctrn_result ORDER BY image_alt_desc_4 DESC',
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
            'SELECT DISTINCT id_0, name_1 FROM (SELECT b0_.id AS id_0, a1_.name AS name_1, b0_.author_id AS author_id_2, b0_.category_id AS category_id_3 FROM BlogPost b0_ INNER JOIN Author a1_ ON b0_.author_id = a1_.id) dctrn_result ORDER BY name_1 ASC',
            $query->getSQL()
        );
    }
}

