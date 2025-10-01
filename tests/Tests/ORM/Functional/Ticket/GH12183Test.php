<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH12183Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $article = new CmsArticle();

        $article->topic = 'Loomings';
        $article->text = 'Call me Ishmael.';

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testPaginatorCountWithOutputWalkerAfterQueryHasBeenExecuted(): void
    {
        $query = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a');

        // Paginator::count is right when the query has not yet been executed
        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);
        self::assertSame(1, $paginator->count());

        // Execute the query
        $result = $query->getResult();
        self::assertCount(1, $result);

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);
        self::assertSame(1, $paginator->count());
    }
}
