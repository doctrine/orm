<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * PostFlushEventTest
 */
class PostFlushEventTest extends OrmFunctionalTestCase
{
    private PostFlushListener $listener;

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $this->listener = new PostFlushListener();
        $evm            = $this->_em->getEventManager();
        $evm->addEventListener(Events::postFlush, $this->listener);
    }

    public function testListenerShouldBeNotified(): void
    {
        $this->_em->persist($this->createNewValidUser());
        $this->_em->flush();
        self::assertTrue($this->listener->wasNotified);
    }

    public function testListenerShouldNotBeNotifiedWhenFlushThrowsException(): void
    {
        $user           = new CmsUser();
        $user->username = 'dfreudenberger';
        $this->_em->persist($user);
        $exceptionRaised = false;

        try {
            $this->_em->flush();
        } catch (Exception) {
            $exceptionRaised = true;
        }

        self::assertTrue($exceptionRaised);
        self::assertFalse($this->listener->wasNotified);
    }

    public function testListenerShouldReceiveEntityManagerThroughArgs(): void
    {
        $this->_em->persist($this->createNewValidUser());
        $this->_em->flush();
        $receivedEm = $this->listener->receivedArgs->getObjectManager();
        self::assertSame($this->_em, $receivedEm);
    }

    private function createNewValidUser(): CmsUser
    {
        $user           = new CmsUser();
        $user->username = 'dfreudenberger';
        $user->name     = 'Daniel Freudenberger';

        return $user;
    }
}

class PostFlushListener
{
    /** @var bool */
    public $wasNotified = false;

    /** @var PostFlushEventArgs */
    public $receivedArgs;

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->wasNotified  = true;
        $this->receivedArgs = $args;
    }
}
