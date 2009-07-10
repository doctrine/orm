<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\ORM\QueryBuilder;

require_once __DIR__ . '/../../TestInit.php';

class QueryBuilderTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testExecute()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');

        $results = $qb->execute();
        $this->assertEquals('Doctrine\Common\Collections\Collection', get_class($results));
    }

    public function testSetMaxResultsAndSetFirstResultZero()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->setMaxResults(10)
            ->setFirstResult(0);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ OFFSET 0 LIMIT 10', $qb->getQuery()->getSql());
    }

    public function testSetMaxResultsAndSetFirstResult()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->setMaxResults(10)
            ->setFirstResult(10);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ OFFSET 10 LIMIT 10', $qb->getQuery()->getSql());
    }

    public function testRemoveSetMaxResultsAndSetFirstResult()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->setMaxResults(10)
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->setFirstResult(null);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_', $qb->getQuery()->getSql());
    }

    public function testOnlyFirstResult()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->setMaxResults(10);

        $this->assertEquals('SELECT c0_.id AS id0, c0_.status AS status1, c0_.username AS username2, c0_.name AS name3 FROM cms_users c0_ LIMIT 10', $qb->getQuery()->getSql());
    }
}