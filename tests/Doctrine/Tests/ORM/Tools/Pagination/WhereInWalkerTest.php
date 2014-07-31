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
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testCountQuery_MixedResultsWithName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT a0_.id AS id_0, a0_.name AS name_1, sum(a0_.name) AS sclr_2 FROM Author a0_ WHERE a0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_SingleWhere()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_MultipleWhereWithAnd()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND 2 = 2 AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_MultipleWhereWithOr()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 OR 2 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_MultipleWhereWithMixed_1()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE (1 = 1 OR 2 = 2) AND 3 = 3'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND 3 = 3 AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_MultipleWhereWithMixed_2()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE 1 = 1 AND 2 = 2 OR 3 = 3'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 AND 2 = 2 OR 3 = 3) AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }

    public function testWhereInQuery_WhereNot()
    {
        $query = $this->entityManager->createQuery(
            'SELECT u, g FROM Doctrine\Tests\ORM\Tools\Pagination\User u JOIN u.groups g WHERE NOT 1 = 2'
        );
        $whereInQuery = clone $query;
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT u0_.id AS id_0, g1_.id AS id_1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (NOT 1 = 2) AND u0_.id IN (?)", $whereInQuery->getSql()
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
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ INNER JOIN Category c1_ ON (b0_.category_id = c1_.id) WHERE b0_.id IN (?)", $whereInQuery->getSql()
        );
    }
    
    public function testWhereInQueryWithArbitraryJoin_SingleWhere()
    {
        $whereInQuery = $this->entityManager->createQuery(
            'SELECT p FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN Doctrine\Tests\ORM\Tools\Pagination\Category c WITH p.category = c WHERE 1 = 1'
        );
        $whereInQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\WhereInWalker'));
        $whereInQuery->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, 10);

        $this->assertEquals(
            "SELECT b0_.id AS id_0, b0_.author_id AS author_id_1, b0_.category_id AS category_id_2 FROM BlogPost b0_ INNER JOIN Category c1_ ON (b0_.category_id = c1_.id) WHERE 1 = 1 AND b0_.id IN (?)", $whereInQuery->getSql()
        );
    }
}

