<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * ManyToManyEventTest
 */
class ManyToManyEventTest extends OrmFunctionalTestCase
{
    private PostUpdateListener $listener;

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $this->listener = new PostUpdateListener();
        $evm            = $this->_em->getEventManager();
        $evm->addEventListener(Events::postUpdate, $this->listener);
    }

    public function testListenerShouldBeNotifiedWhenNewCollectionEntryAdded(): void
    {
        $user        = $this->createNewValidUser();
        $group       = new CmsGroup();
        $group->name = 'admins';

        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();
        self::assertFalse($this->listener->wasNotified);

        $user->addGroup($group);
        $this->_em->flush();

        self::assertTrue($this->listener->wasNotified);
    }

    public function testListenerShouldBeNotifiedWhenCollectionEntryRemoved(): void
    {
        $user        = $this->createNewValidUser();
        $group       = new CmsGroup();
        $group->name = 'admins';
        $user->addGroup($group);

        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();
        self::assertFalse($this->listener->wasNotified);

        $user->getGroups()->removeElement($group);
        $this->_em->flush();

        self::assertTrue($this->listener->wasNotified);
    }

    private function createNewValidUser(): CmsUser
    {
        $user           = new CmsUser();
        $user->username = 'fran6co';
        $user->name     = 'Francisco Facioni';
        $group          = new CmsGroup();
        $group->name    = 'users';
        $user->addGroup($group);

        return $user;
    }
}

class PostUpdateListener
{
    /** @var bool */
    public $wasNotified = false;

    public function postUpdate($args): void
    {
        $this->wasNotified = true;
    }
}
