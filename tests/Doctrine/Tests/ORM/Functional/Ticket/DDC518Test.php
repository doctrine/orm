<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

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
    }
}