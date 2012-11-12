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

    public function testLimitSubqueryPg()
    {
        $odp = $this->entityManager->getConnection()->getDatabasePlatform();
        $this->entityManager->getConnection()->setDatabasePlatform(new \Doctrine\DBAL\Platforms\PostgreSqlPlatform);

        $this->testLimitSubquery();

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

