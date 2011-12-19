<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Qelista\User;

use Doctrine\Tests\Models\Qelista\ShoppingList;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1545
 */
class DDC1545Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $articleId;

    private $userId;

    private $user2Id;

    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    private function initDb($link)
    {
        $article = new CmsArticle();
        $article->topic = 'foo';
        $article->text = 'foo';

        $user = new CmsUser();
        $user->status = 'foo';
        $user->username = 'foo';
        $user->name = 'foo';

        $user2 = new CmsUser();
        $user2->status = 'bar';
        $user2->username = 'bar';
        $user2->name = 'bar';

        if ($link) {
            $article->user = $user;
        }

        $this->_em->persist($article);
        $this->_em->persist($user);
        $this->_em->persist($user2);
        $this->_em->flush();
        $this->_em->clear();

        $this->articleId = $article->id;
        $this->userId = $user->id;
        $this->user2Id = $user2->id;
    }

    public function testLinkObjects()
    {
        $this->initDb(false);

        // don't join association
        $article = $this->_em->find('Doctrine\Tests\Models\Cms\CmsArticle', $this->articleId);

        $user = $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $this->userId);

        $article->user = $user;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNotNull($article->user);
        $this->assertEquals($user->id, $article->user->id);
    }

    public function testLinkObjectsWithAssociationLoaded()
    {
        $this->initDb(false);

        // join association
        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $user = $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $this->userId);

        $article->user = $user;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNotNull($article->user);
        $this->assertEquals($user->id, $article->user->id);
    }

    public function testUnlinkObjects()
    {
        $this->initDb(true);

        // don't join association
        $article = $this->_em->find('Doctrine\Tests\Models\Cms\CmsArticle', $this->articleId);

        $article->user = null;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNull($article->user);
    }

    public function testUnlinkObjectsWithAssociationLoaded()
    {
        $this->initDb(true);

        // join association
        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $article->user = null;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNull($article->user);
    }

    public function testChangeLink()
    {
        $this->initDb(false);

        // don't join association
        $article = $this->_em->find('Doctrine\Tests\Models\Cms\CmsArticle', $this->articleId);

        $user2 = $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $this->user2Id);

        $article->user = $user2;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNotNull($article->user);
        $this->assertEquals($user2->id, $article->user->id);
    }

    public function testChangeLinkWithAssociationLoaded()
    {
        $this->initDb(false);

        // join association
        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $user2 = $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $this->user2Id);

        $article->user = $user2;

        $this->_em->flush();
        $this->_em->clear();

        $article = $this->_em
            ->createQuery('SELECT a, u FROM Doctrine\Tests\Models\Cms\CmsArticle a LEFT JOIN a.user u WHERE a.id = :id')
            ->setParameter('id', $this->articleId)
            ->getOneOrNullResult();

        $this->assertNotNull($article->user);
        $this->assertEquals($user2->id, $article->user->id);
    }
}
