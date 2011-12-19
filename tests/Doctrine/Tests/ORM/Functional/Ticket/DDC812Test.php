<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

require_once __DIR__ . '/../../../TestInit.php';

class DDC812Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @group DDC-812
     */
    public function testFetchJoinInitializesPreviouslyUninitializedCollectionOfManagedEntity()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $article = new CmsArticle;
        $article->topic = "hello";
        $article->text = "talk talk talk";

        $comment = new CmsComment;
        $comment->topic = "good!";
        $comment->text = "stuff!";
        $comment->article = $article;

        $this->_em->persist($article);
        $this->_em->persist($comment);
        $this->_em->flush();
        $this->_em->clear();

        $article2 = $this->_em->find(get_class($article), $article->id);

        $article2Again = $this->_em->createQuery(
            "select a, c from Doctrine\Tests\Models\CMS\CmsArticle a join a.comments c where a.id = ?1")
            ->setParameter(1, $article->id)
            ->getSingleResult();

        $this->assertTrue($article2Again === $article2);
        $this->assertTrue($article2Again->comments->isInitialized());
    }
}
