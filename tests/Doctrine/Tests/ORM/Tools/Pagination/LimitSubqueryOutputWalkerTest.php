<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

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
            "SELECT DISTINCT id0 FROM (SELECT m0_.id AS id0, m0_.title AS title1, c1_.id AS id2, a2_.id AS id3, a2_.name AS name4, m0_.author_id AS author_id5, m0_.category_id AS category_id6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result", $limitQuery->getSql()
        );
    }

    public function testLimitSubqueryWithSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id0, title1 FROM (SELECT m0_.id AS id0, m0_.title AS title1, c1_.id AS id2, a2_.id AS id3, a2_.name AS name4, m0_.author_id AS author_id5, m0_.category_id AS category_id6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id ORDER BY m0_.title ASC) dctrn_result ORDER BY title1 ASC", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id1, sclr0 FROM (SELECT COUNT(g0_.id) AS sclr0, u1_.id AS id1, g0_.id AS id2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id ORDER BY sclr0 ASC) dctrn_result ORDER BY sclr0 ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id1, sclr0 FROM (SELECT COUNT(g0_.id) AS sclr0, u1_.id AS id1, g0_.id AS id2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id ORDER BY sclr0 ASC, u1_.id DESC) dctrn_result ORDER BY sclr0 ASC, id1 DESC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithHiddenScalarSortPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $query = $this->entityManager->createQuery(
           'SELECT u, g, COUNT(g.id) AS hidden g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id1, sclr0 FROM (SELECT COUNT(g0_.id) AS sclr0, u1_.id AS id1, g0_.id AS id2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id ORDER BY sclr0 ASC, u1_.id DESC) dctrn_result ORDER BY sclr0 ASC, id1 DESC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $this->testLimitSubquery();

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }
    
    public function testLimitSubqueryWithSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a ORDER BY p.title');
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID0, TITLE1 FROM (SELECT m0_.id AS ID0, m0_.title AS TITLE1, c1_.id AS ID2, a2_.id AS ID3, a2_.name AS NAME4, m0_.author_id AS AUTHOR_ID5, m0_.category_id AS CATEGORY_ID6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id ORDER BY m0_.title ASC) dctrn_result ORDER BY TITLE1 ASC", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithScalarSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID1, SCLR0 FROM (SELECT COUNT(g0_.id) AS SCLR0, u1_.id AS ID1, g0_.id AS ID2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id ORDER BY SCLR0 ASC) dctrn_result ORDER BY SCLR0 ASC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryWithMixedSortOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT u, g, COUNT(g.id) AS g_quantity FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g ORDER BY g_quantity, u.id DESC'
        );
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID1, SCLR0 FROM (SELECT COUNT(g0_.id) AS SCLR0, u1_.id AS ID1, g0_.id AS ID2 FROM User u1_ INNER JOIN user_group u2_ ON u1_.id = u2_.user_id INNER JOIN groups g0_ ON g0_.id = u2_.group_id ORDER BY SCLR0 ASC, u1_.id DESC) dctrn_result ORDER BY SCLR0 ASC, ID1 DESC",
            $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testLimitSubqueryOracle()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\OraclePlatform);

        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\MyBlogPost p JOIN p.category c JOIN p.author a');
        $query->expireQueryCache(true);
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT ID0 FROM (SELECT m0_.id AS ID0, m0_.title AS TITLE1, c1_.id AS ID2, a2_.id AS ID3, a2_.name AS NAME4, m0_.author_id AS AUTHOR_ID5, m0_.category_id AS CATEGORY_ID6 FROM MyBlogPost m0_ INNER JOIN Category c1_ ON m0_.category_id = c1_.id INNER JOIN Author a2_ ON m0_.author_id = a2_.id) dctrn_result", $limitQuery->getSql()
        );

        $this->entityManager->getConnection()->setDatabasePlatform($odp);
    }

    public function testCountQuery_MixedResultsWithName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a');
        $limitQuery = clone $query;
        $limitQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker');

        $this->assertEquals(
            "SELECT DISTINCT id0 FROM (SELECT a0_.id AS id0, a0_.name AS name1, sum(a0_.name) AS sclr2 FROM Author a0_) dctrn_result", $limitQuery->getSql()
        );
    }
}

