<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-2409
 */
class DDC2409Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue(): void
    {
        $em  = $this->_em;
        $uow = $em->getUnitOfWork();

        $originalArticle = new CmsArticle();
        $originalUser    = new CmsUser();

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

        $article = $em->find(CmsArticle::class, $originalArticle->id);
        $user    = new CmsUser();

        $user->name     = 'Doctrine Bot 2.0';
        $user->username = 'BotDoctrine2';
        $user->status   = 'new';

        $article->setAuthor($user);

        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalArticle));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($originalUser));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($article));
        $this->assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));

        $em->clear(CmsUser::class);
        $em->clear(CmsArticle::class);

        $userMerged    = $em->merge($user);
        $articleMerged = $em->merge($article);

        $this->assertEquals(UnitOfWork::STATE_NEW, $uow->getEntityState($user));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getEntityState($article));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($userMerged));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getEntityState($articleMerged));

        $this->assertNotSame($user, $userMerged);
        $this->assertNotSame($article, $articleMerged);
        $this->assertNotSame($userMerged, $articleMerged->user);
        $this->assertSame($user, $articleMerged->user);
        $this->assertHasDeprecationMessages();
    }
}
