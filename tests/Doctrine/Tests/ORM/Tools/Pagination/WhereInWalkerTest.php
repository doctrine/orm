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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT a0_.id AS id0, a0_.name AS name1, sum(a0_.name) AS sclr2 FROM Author a0_ WHERE a0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE 1 = 1 AND 2 = 2 AND u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 OR 2 = 2) AND 3 = 3 AND u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (1 = 1 AND 2 = 2 OR 3 = 3) AND u0_.id IN (?)", $whereInQuery->getSql()
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
            "SELECT u0_.id AS id0, g1_.id AS id1 FROM User u0_ INNER JOIN user_group u2_ ON u0_.id = u2_.user_id INNER JOIN groups g1_ ON g1_.id = u2_.group_id WHERE (NOT 1 = 2) AND u0_.id IN (?)", $whereInQuery->getSql()
        );
    }
}

