<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1040
 */
class DDC1040Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testReuseNamedEntityParameter()
    {
        $user = new CmsUser();
        $user->name = "John Galt";
        $user->username = "jgalt";
        $user->status = "inactive";
        
        $article = new CmsArticle();
        $article->topic = "This is John Galt speaking!";
        $article->text = "Yadda Yadda!";
        $article->setAuthor($user);

        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->flush();

        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = :author";
        $this->_em->createQuery($dql)
                  ->setParameter('author', $user)
                  ->getResult();

        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.user = :author AND a.user = :author";
        $this->_em->createQuery($dql)
                  ->setParameter('author', $user)
                  ->getResult();

        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.topic = :topic AND a.user = :author AND a.user = :author  AND a.text = :text";
        $farticle = $this->_em->createQuery($dql)
                  ->setParameter('author', $user)
                  ->setParameter('topic', 'This is John Galt speaking!')
                  ->setParameter('text', 'Yadda Yadda!')
                  ->getSingleResult();

        $this->assertSame($article, $farticle);
    }

    public function testUseMultiplePositionalParameters()
    {
        $user = new CmsUser();
        $user->name = "John Galt";
        $user->username = "jgalt";
        $user->status = "inactive";

        $article = new CmsArticle();
        $article->topic = "This is John Galt speaking!";
        $article->text = "Yadda Yadda!";
        $article->setAuthor($user);

        $this->_em->persist($user);
        $this->_em->persist($article);
        $this->_em->flush();

        $dql = "SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a WHERE a.topic = ?1 AND a.user = ?2 AND a.user = ?3 AND a.text = ?4";
        $farticle = $this->_em->createQuery($dql)
                  ->setParameter(1, 'This is John Galt speaking!')
                  ->setParameter(2, $user)
                  ->setParameter(3, $user)
                  ->setParameter(4, 'Yadda Yadda!')
                  ->getSingleResult();

        $this->assertSame($article, $farticle);
    }
}