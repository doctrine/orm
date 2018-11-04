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
    /** @var PostFlushListener */
    private $listener;

    protected function setUp() : void
    {
        $this->useModelSet('cms');
        parent::setUp();
        $this->listener = new PostFlushListener();
        $evm            = $this->em->getEventManager();
        $evm->addEventListener(Events::postFlush, $this->listener);
    }

    public function testListenerShouldBeNotified() : void
    {
        $this->em->persist($this->createNewValidUser());
        $this->em->flush();
        self::assertTrue($this->listener->wasNotified);
    }

    public function testListenerShouldNotBeNotifiedWhenFlushThrowsException() : void
    {
        $user           = new CmsUser();
        $user->username = 'dfreudenberger';
        $this->em->persist($user);
        $exceptionRaised = false;

        try {
            $this->em->flush();
        } catch (Exception $ex) {
            $exceptionRaised = true;
        }

        self::assertTrue($exceptionRaised);
        self::assertFalse($this->listener->wasNotified);
    }

    public function testListenerShouldReceiveEntityManagerThroughArgs() : void
    {
        $this->em->persist($this->createNewValidUser());
        $this->em->flush();
        $receivedEm = $this->listener->receivedArgs->getEntityManager();
        self::assertSame($this->em, $receivedEm);
    }

    /**
     * @return CmsUser
     */
    private function createNewValidUser()
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

    public function postFlush(PostFlushEventArgs $args)
    {
        $this->wasNotified  = true;
        $this->receivedArgs = $args;
    }
}
