<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;

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
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $article = new CmsArticle;
        $article->topic = "hello";
        $article->text = "talk talk talk";

        $comment = new CmsComment;
        $comment->topic = "good!";
        $comment->text = "stuff!";
        $comment->article = $article;

        $this->em->persist($article);
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();

        $article2 = $this->em->find(get_class($article), $article->id);

        $article2Again = $this->em->createQuery(
            "select a, c from Doctrine\Tests\Models\CMS\CmsArticle a join a.comments c where a.id = ?1")
            ->setParameter(1, $article->id)
            ->getSingleResult();

        self::assertTrue($article2Again === $article2);
        self::assertTrue($article2Again->comments->isInitialized());
    }
}
