<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC518Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testMergeWithRelatedNew()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";

        $this->em->persist($article);
        $this->em->flush();
        $this->em->detach($article);
        $this->em->clear();

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin Eberlei";
        $user->status = "active";
        $article->user = $user;

        $this->em->persist($user);
        $managedArticle = $this->em->merge($article);

        self::assertSame($article->user, $managedArticle->user);
    }
}
