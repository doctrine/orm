<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;

/** @group DDC-1129 */
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

        self::assertEquals(1, $article->version);

        $class = $this->_em->getClassMetadata(CmsArticle::class);
        $uow   = $this->_em->getUnitOfWork();

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        self::assertCount(0, $changeSet, 'No changesets should be computed.');

        $article->text = 'This is John Galt speaking.';
        $this->_em->flush();

        self::assertEquals(2, $article->version);

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        self::assertCount(0, $changeSet, 'No changesets should be computed.');
    }
}
