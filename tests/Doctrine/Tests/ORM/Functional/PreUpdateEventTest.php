<?php
/**
 * @author   : Sören Bernstein
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class PreUpdateEventTest extends OrmFunctionalTestCase
{
    /** @var PreUpdateListener */
    private $listener;

    public function testListenerShouldBeNotifiedIfCollectionIsCleared(): void
    {
        $user = $this->createNewValidUser();
        $this->_em->persist($user);
        $this->_em->flush();
        self::assertFalse($this->listener->wasNotified);

        $user->getGroups()->clear();
        $this->_em->flush();
        self::assertTrue($this->listener->wasNotified);
    }

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
        $this->listener = new PreUpdateListener();
        $evm = $this->_em->getEventManager();
        $evm->addEventListener(Events::preUpdate, $this->listener);
    }

    private function createNewValidUser(): CmsUser
    {
        $user = new CmsUser();
        $user->username = 'dfreudenberger';
        $user->name = 'Daniel Freudenberger';

        /** @var CmsGroup $group */
        $group = new CmsGroup();
        $group->addUser($user);
        $group->setName('test group');

        $user->addGroup($group);

        return $user;
    }
}

class PreUpdateListener
{
    /** @var bool */
    public $wasNotified = false;

    /** @var LifecycleEventArgs */
    public $receivedArgs;

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->wasNotified = true;
        $this->receivedArgs = $args;
    }
}
