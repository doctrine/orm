<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1129
 */
class DDC1129Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testVersionFieldIgnoredInChangesetComputation()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "I don't know.";
        $article->topic = "Who is John Galt?";

        $this->_em->persist($article);
        $this->_em->flush();

        $this->assertEquals(1, $article->version);

        $class = $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsArticle');
        $uow = $this->_em->getUnitOfWork();

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        $this->assertEquals(0, count($changeSet), "No changesets should be computed.");

        $article->text = "This is John Galt speaking.";
        $this->_em->flush();

        $this->assertEquals(2, $article->version);

        $uow->computeChangeSet($class, $article);
        $changeSet = $uow->getEntityChangeSet($article);
        $this->assertEquals(0, count($changeSet), "No changesets should be computed.");
    }
}