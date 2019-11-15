<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\VerifyDeprecations;

class DDC518Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    use VerifyDeprecations;

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

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->detach($article);
        $this->_em->clear();

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin Eberlei";
        $user->status = "active";
        $article->user = $user;

        $this->_em->persist($user);
        $managedArticle = $this->_em->merge($article);

        $this->assertSame($article->user, $managedArticle->user);
        $this->assertHasDeprecationMessages();
    }
}
