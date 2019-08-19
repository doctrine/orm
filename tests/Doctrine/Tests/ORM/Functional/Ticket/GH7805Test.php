<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7805Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testPaginationWithSimpleArithmetic()
    {
        $article = new CmsArticle;
        $article->topic = 'Test SimpleArithmetic ORDER BY';
        $article->text = 'This test fails on MySQL.';

        $this->_em->persist($article);
        $this->_em->flush();

        $query = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a ORDER BY a.topic + 0 ASC');
        $query->setFirstResult(0);
        $query->setMaxResults(1);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);
        $this->assertEquals(1, count(iterator_to_array($paginator)));
    }
}
