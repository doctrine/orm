<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC518Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testMergeWithRelatedNew(): void
    {
        $article        = new CmsArticle();
        $article->text  = 'foo';
        $article->topic = 'bar';

        $this->_em->persist($article);
        $this->_em->flush();
        $this->_em->detach($article);
        $this->_em->clear();

        $user           = new CmsUser();
        $user->username = 'beberlei';
        $user->name     = 'Benjamin Eberlei';
        $user->status   = 'active';
        $article->user  = $user;

        $this->_em->persist($user);
        $managedArticle = $this->_em->merge($article);

        self::assertSame($article->user, $managedArticle->user);
    }
}
