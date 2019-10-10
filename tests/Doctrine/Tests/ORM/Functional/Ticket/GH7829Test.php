<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH7829
 */
final class GH7829Test extends OrmFunctionalTestCase
{
    /** @var DebugStack */
    private $logger;

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();

        $article = new CmsArticle();

        $article->topic = 'Skip Limit Subquery';
        $article->text  = 'Skip Limit Subquery if not required.';

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->getConnection()->getConfiguration()->setSQLLogger($this->logger = new DebugStack());
    }

    public function testPaginatorWithLimitSubquery() : void
    {
        $query = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a');
        $query->setMaxResults(1);

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);

        $paginator->count();
        $paginator->getIterator();

        $this->assertCount(3, $this->logger->queries);
    }

    public function testPaginatorWithLimitSubquerySkipped() : void
    {
        $query = $this->_em->createQuery('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a');

        $paginator = new Paginator($query, true);
        $paginator->setUseOutputWalkers(false);

        $paginator->count();
        $paginator->getIterator();

        $this->assertCount(2, $this->logger->queries);
    }
}
