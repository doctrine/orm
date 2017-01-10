<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsArticle;

/**
 * @group DDC-2409
 */
class DDC2409Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue()
    {
        $em     = $this->em;
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

        $article  = $em->find(CmsArticle::class, $originalArticle->id);
        $user     = new CmsUser();

        $user->name     = 'Doctrine Bot 2.0';
        $user->username = 'BotDoctrine2';
        $user->status   = 'new';

        $article->setAuthor($user);

        self::assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalArticle));
        self::assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalUser));
        self::assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($article));
        self::assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));

        $em->detach($user);
        $em->detach($article);

        $userMerged     = $em->merge($user);
        $articleMerged  = $em->merge($article);

        self::assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));
        self::assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($article));
        self::assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($userMerged));
        self::assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($articleMerged));

        self::assertNotSame($user, $userMerged);
        self::assertNotSame($article, $articleMerged);
        self::assertNotSame($userMerged, $articleMerged->user);
        self::assertSame($user, $articleMerged->user);
    }
}
