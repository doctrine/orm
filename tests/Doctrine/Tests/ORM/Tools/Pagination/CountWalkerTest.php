<?php

namespace Doctrine\Tests\ORM\Tools\Pagination;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;

/**
 * @group DDC-1613
 */
class CountWalkerTest extends PaginationTestCase
{
    public function testCountQuery()
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT count(DISTINCT b0_.id) AS sclr0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id", $query->getSql()
        );
    }

    public function testCountQuery_MixedResultsWithName()
    {
        $query = $this->entityManager->createQuery(
            'SELECT a, sum(a.name) as foo FROM Doctrine\Tests\ORM\Tools\Pagination\Author a');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT count(DISTINCT a0_.id) AS sclr0 FROM Author a0_", $query->getSql()
        );
    }

    public function testCountQuery_KeepsGroupBy()
    {
        $query = $this->entityManager->createQuery(
            'SELECT b FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost b GROUP BY b.id');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT count(DISTINCT b0_.id) AS sclr0 FROM BlogPost b0_ GROUP BY b0_.id", $query->getSql()
        );
    }

    public function testCountQuery_RemovesOrderBy()
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a ORDER BY a.name');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT count(DISTINCT b0_.id) AS sclr0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id", $query->getSql()
        );
    }

    public function testCountQuery_RemovesLimits()
    {
        $query = $this->entityManager->createQuery(
            'SELECT p, c, a FROM Doctrine\Tests\ORM\Tools\Pagination\BlogPost p JOIN p.category c JOIN p.author a');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setHint(CountWalker::HINT_DISTINCT, true);
        $query->setFirstResult(null)->setMaxResults(null);

        $this->assertEquals(
            "SELECT count(DISTINCT b0_.id) AS sclr0 FROM BlogPost b0_ INNER JOIN Category c1_ ON b0_.category_id = c1_.id INNER JOIN Author a2_ ON b0_.author_id = a2_.id", $query->getSql()
        );
    }

    public function testCountQuery_HavingException()
    {
        $query = $this->entityManager->createQuery(
            "SELECT g, COUNT(u.id) AS userCount FROM Doctrine\Tests\Models\CMS\CmsGroup g LEFT JOIN g.users u GROUP BY g.id HAVING userCount > 0"
        );
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('Doctrine\ORM\Tools\Pagination\CountWalker'));
        $query->setFirstResult(null)->setMaxResults(null);

        $this->setExpectedException(
            'RuntimeException',
            'Cannot count query that uses a HAVING clause. Use the output walkers for pagination'
        );

        $query->getSql();
    }
}

