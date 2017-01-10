<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * PostFlushEventTest
 *
 * @author Daniel Freudenberger <df@rebuy.de>
 */
class PostFlushEventTest extends OrmFunctionalTestCase
{
    /**
     * @var PostFlushListener
     */
    private $listener;

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
        $this->listener = new PostFlushListener();
        $evm = $this->em->getEventManager();
        $evm->addEventListener(Events::postFlush, $this->listener);
    }

    public function testListenerShouldBeNotified()
    {
        $this->em->persist($this->createNewValidUser());
        $this->em->flush();
        self::assertTrue($this->listener->wasNotified);
    }

    public function testListenerShouldNotBeNotifiedWhenFlushThrowsException()
    {
        $user = new CmsUser();
        $user->username = 'dfreudenberger';
        $this->em->persist($user);
        $exceptionRaised = false;

        try {
            $this->em->flush();
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }

        self::assertTrue($exceptionRaised);
        self::assertFalse($this->listener->wasNotified);
    }

    public function testListenerShouldReceiveEntityManagerThroughArgs()
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
        $user = new CmsUser();
        $user->username = 'dfreudenberger';
        $user->name = 'Daniel Freudenberger';
        return $user;
    }
}

class PostFlushListener
{
    /**
     * @var bool
     */
    public $wasNotified = false;

    /**
     * @var PostFlushEventArgs
     */
    public $receivedArgs;

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $this->wasNotified = true;
        $this->receivedArgs = $args;
    }
}
