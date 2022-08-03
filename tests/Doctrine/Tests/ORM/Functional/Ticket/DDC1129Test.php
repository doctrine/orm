<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * @group DDC-1129
 */
class DDC1129Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testVersionFieldIgnoredInChangesetComputation(): void
    {
        $article        = new CmsArticle();
        $article->text  = "I don't know.";
        $article->topic = 'Who is John Galt?';

        $this->_em->persist($article);
        $this->_em->flush();

        $this->assertEquals(1, $article->version);

        $class = $this->_em->getClassMetadata(CmsArticle::class);
        $uow   = $this->_em->getUnitOfWork();

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        $this->assertEquals(0, count($changeSet), 'No changesets should be computed.');

        $article->text = 'This is John Galt speaking.';
        $this->_em->flush();

        $this->assertEquals(2, $article->version);

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        $this->assertEquals(0, count($changeSet), 'No changesets should be computed.');
    }
}
