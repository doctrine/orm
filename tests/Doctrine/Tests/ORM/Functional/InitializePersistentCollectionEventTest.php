<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\InitializePersistentCollectionEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class InitializePersistentCollectionEventTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testEventIsCalledOnPersistentCollectionInitialization()
    {
        $listener = new InitializePersistentCollectionListener();
        $this->_em->getEventManager()->addEventListener(Events::initializePersistentCollection, $listener);

        // Prerequisite: create, persist and flush an entity
        $user           = new CmsUser();
        $user->username = 'username';
        $user->name     = 'name';
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        /** @var CmsUser $user */
        $user   = $this->_em->getRepository(CmsUser::class)->findAll()[0];
        $phones = $user->getPhonenumbers();
        // Action: this triggers the actual collection initialization
        $phones->first();

        // Expectation: initialization event has been called
        $this->assertTrue($listener->called);
    }
}

class InitializePersistentCollectionListener
{
    public $called = false;

    public function initializePersistentCollection(InitializePersistentCollectionEventArgs $args)
    {
        $this->called = true;
    }
}
