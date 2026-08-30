<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
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
        $article->text  = 'Call me Ishmael.';

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testPaginatorCountWithTreeWalkerAfterQueryHasBeenExecuted(): void
    {
        $query = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a');

        $paginator = new OffsetPaginator(true, false);
        $window    = new Window(0, 10);

        // the total count is right when the query has not yet been executed
        self::assertSame(1, $paginator->paginate($query, $window)->getTotalCount());

        // Execute the query
        $result = $query->getResult();
        self::assertCount(1, $result);

        self::assertSame(1, $paginator->paginate($query, $window)->getTotalCount());
    }
}
