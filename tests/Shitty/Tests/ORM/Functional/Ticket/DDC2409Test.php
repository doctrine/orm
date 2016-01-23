<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\ORM\UnitOfWork;
use Shitty\Tests\Models\CMS\CmsUser;
use Shitty\Tests\Models\CMS\CmsArticle;

/**
 * @group DDC-2409
 */
class DDC2409Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue()
    {
        $em     = $this->_em;
        $uow    = $em->getUnitOfWork();
        
        $originalArticle  = new CmsArticle();
        $originalUser     = new CmsUser();

        $originalArticle->topic = 'Unit Test';
        $originalArticle->text  = 'How to write a test';

        $originalUser->name     = 'Doctrine Bot';
        $originalUser->username = 'DoctrineBot';
        $originalUser->status   = 'active';

        $originalUser->addArticle($originalArticle);

        $em->persist($originalUser);
        $em->persist($originalArticle);
        $em->flush();
        $em->clear();

        $article  = $em->find('Doctrine\Tests\Models\CMS\CmsArticle', $originalArticle->id);
        $user     = new CmsUser();

        $user->name     = 'Doctrine Bot 2.0';
        $user->username = 'BotDoctrine2';
        $user->status   = 'new';

        $article->setAuthor($user);

        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalArticle));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalUser));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($article));
        $this->assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));

        $em->detach($user);
        $em->detach($article);

        $userMerged     = $em->merge($user);
        $articleMerged  = $em->merge($article);

        $this->assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($article));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($userMerged));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($articleMerged));

        $this->assertNotSame($user, $userMerged);
        $this->assertNotSame($article, $articleMerged);
        $this->assertNotSame($userMerged, $articleMerged->user);
        $this->assertSame($user, $articleMerged->user);
    }
}