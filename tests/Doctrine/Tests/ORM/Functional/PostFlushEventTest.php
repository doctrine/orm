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
 * @author Tom Lei <tomlei90@gmail.com>
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
        $evm = $this->_em->getEventManager();
        $evm->addEventListener(Events::postFlush, $this->listener);
    }

    protected function tearDown()
    {
      $this->listener->postFlushAssertions = null;
      parent::tearDown();
    }

    public function testListenerShouldBeNotified()
    {
        $this->_em->persist($this->createNewValidUser());
        $this->_em->flush();
        $this->assertTrue($this->listener->wasNotified);
    }

    public function testListenerShouldNotBeNotifiedWhenFlushThrowsException()
    {
        $user = new CmsUser();
        $user->username = 'dfreudenberger';
        $this->_em->persist($user);
        $exceptionRaised = false;

        try {
            $this->_em->flush();
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }

        $this->assertTrue($exceptionRaised);
        $this->assertFalse($this->listener->wasNotified);
    }

    public function testListenerShouldReceiveEntityManagerThroughArgs()
    {
        $this->_em->persist($this->createNewValidUser());
        $this->_em->flush();
        $receivedEm = $this->listener->receivedArgs->getEntityManager();
        $this->assertSame($this->_em, $receivedEm);
    }

    public function testUnitOfWorkInPostFlushShouldHaveInsertedEntities()
    {
        $testUser = $this->createNewUser('tom.lei@applesauce', 'Tom Lei');

        $this->listener->postFlushAssertions = function(PostFlushEventArgs $args) use ($testUser) {
            $uow = $args->getEntityManager()->getUnitOfWork();
            $insertedEntities = $uow->getInsertedEntities();
            $this->assertCount(1, $insertedEntities);
            $this->assertEquals($testUser, current($insertedEntities));
        };

        $this->_em->persist($testUser);
        $this->_em->flush();
    }

    public function testUnitOfWorkInPostFlushShouldHaveUpdatedEntities()
    {
        $testUser = $this->createNewUser('tom.lei@applesauce', 'Tom Lei');
        $this->_em->persist($testUser);
        $this->_em->flush();

        $testUser->status = 'InProgress';

        $this->listener->postFlushAssertions = function(PostFlushEventArgs $args) use ($testUser) {
            $uow = $args->getEntityManager()->getUnitOfWork();
            $updatedEntities = $uow->getUpdatedEntities();
            $this->assertCount(1, $updatedEntities);
            $this->assertEquals($testUser, current($updatedEntities));
        };

        $this->_em->flush();
    }

    public function testUnitOfWorkInPostFlushShouldHaveDeletedEntities()
    {
        $testUser = $this->createNewUser('tom.lei@applesauce', 'Tom Lei');
        $this->_em->persist($testUser);
        $this->_em->flush();

        $this->listener->postFlushAssertions = function(PostFlushEventArgs $args) use ($testUser) {
            $uow = $args->getEntityManager()->getUnitOfWork();
            $deletedEntities = $uow->getDeletedEntities();
            $this->assertCount(1, $deletedEntities);
            $this->assertEquals($testUser, current($deletedEntities));
        };

        $this->_em->remove($testUser);
        $this->_em->flush();
    }

    /**
     * @return CmsUser
     */
    private function createNewValidUser()
    {
        return $this->createNewUser('dfreudenberger', 'Daniel Freudenberger');
    }

    /**
     * @return CmsUser
     */
    private function createNewUser($userName, $name)
    {
        $user = new CmsUser();
        $user->username = $userName;
        $user->name = $name;

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
     * @var callable | null
     */
    public $postFlushAssertions = null;

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $this->wasNotified = true;
        $this->receivedArgs = $args;

        if (is_callable($this->postFlushAssertions)) {
            call_user_func($this->postFlushAssertions, $args);
        }
    }
}
