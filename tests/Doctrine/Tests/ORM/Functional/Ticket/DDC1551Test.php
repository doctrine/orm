<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 *
 * @author Tom Lei <tomlei90@gmail.com>
 */
class DDC1511Test extends \Doctrine\Tests\OrmFunctionalTestCase
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

    public function testUnitOfWorkInPostFlushShouldHaveInsertedEntities()
    {
        $testUser = $this->createNewValidUser('tom.lei@blues', 'Tom Lei');

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
        $testUser = $this->createNewValidUser('tom.lei@molson', 'Tom Lei');
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
        $testUser = $this->createNewValidUser('tom.lei@applesauce', 'Tom Lei');
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
    private function createNewValidUser($userName, $name)
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
     * @var callable | null
     */
    public $postFlushAssertions = null;

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args) {
        if (is_callable($this->postFlushAssertions)) {
            call_user_func($this->postFlushAssertions, $args);
        }
    }
}
