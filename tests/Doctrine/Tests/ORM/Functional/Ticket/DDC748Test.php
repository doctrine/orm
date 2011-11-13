<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsAddress;

require_once __DIR__ . '/../../../TestInit.php';

class DDC748Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testRefreshWithManyToOne()
    {
        $user = new CmsUser();
        $user->name = "beberlei";
        $user->status = "active";
        $user->username = "beberlei";

        $article = new CmsArticle();
        $article->setAuthor($user);
        $article->text = "foo";
        $article->topic = "bar";

        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->flush();

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $user->articles);
        $this->_em->refresh($article);
        $this->assertTrue($article !== $user->articles, "The article should not be replaced on the inverse side of the relation.");
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $user->articles);
    }

    public function testRefreshOneToOne()
    {
        $user = new CmsUser();
        $user->name = "beberlei";
        $user->status = "active";
        $user->username = "beberlei";

        $address = new CmsAddress();
        $address->city = "Bonn";
        $address->country = "Germany";
        $address->street = "A street";
        $address->zip = 12345;
        $address->setUser($user);

        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->flush();

        $this->_em->refresh($address);
        $this->assertSame($user, $address->user);
        $this->assertSame($user->address, $address);
    }
}