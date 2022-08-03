<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;
use function get_class;

/**
 * @group DDC-1643
 */
class DDC1643Test extends OrmFunctionalTestCase
{
    /** @var CmsUser */
    private $user1;

    /** @var CmsUser */
    private $user2;

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();

        $user1           = new CmsUser();
        $user1->username = 'beberlei';
        $user1->name     = 'Benjamin';
        $user1->status   = 'active';
        $group1          = new CmsGroup();
        $group1->name    = 'test';
        $group2          = new CmsGroup();
        $group2->name    = 'test';
        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user2           = new CmsUser();
        $user2->username = 'romanb';
        $user2->name     = 'Roman';
        $user2->status   = 'active';

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->flush();
        $this->_em->clear();

        $this->user1 = $this->_em->find(get_class($user1), $user1->id);
        $this->user2 = $this->_em->find(get_class($user1), $user2->id);
    }

    public function testClonePersistentCollectionAndReuse(): void
    {
        $user1 = $this->user1;

        $user1->groups = clone $user1->groups;

        $this->_em->flush();
        $this->_em->clear();

        $user1 = $this->_em->find(get_class($user1), $user1->id);

        $this->assertEquals(2, count($user1->groups));
    }

    public function testClonePersistentCollectionAndShare(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $user2->groups = clone $user1->groups;

        $this->_em->flush();
        $this->_em->clear();

        $user1 = $this->_em->find(get_class($user1), $user1->id);
        $user2 = $this->_em->find(get_class($user1), $user2->id);

        $this->assertEquals(2, count($user1->groups));
        $this->assertEquals(2, count($user2->groups));
    }

    public function testCloneThenDirtyPersistentCollection(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3        = new CmsGroup();
        $group3->name  = 'test';
        $user2->groups = clone $user1->groups;
        $user2->groups->add($group3);

        $this->_em->persist($group3);
        $this->_em->flush();
        $this->_em->clear();

        $user1 = $this->_em->find(get_class($user1), $user1->id);
        $user2 = $this->_em->find(get_class($user1), $user2->id);

        $this->assertEquals(3, count($user2->groups));
        $this->assertEquals(2, count($user1->groups));
    }

    public function testNotCloneAndPassAroundFlush(): void
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3        = new CmsGroup();
        $group3->name  = 'test';
        $user2->groups = $user1->groups;
        $user2->groups->add($group3);

        $this->assertCount(1, $user1->groups->getInsertDiff());

        $this->_em->persist($group3);
        $this->_em->flush();
        $this->_em->clear();

        $user1 = $this->_em->find(get_class($user1), $user1->id);
        $user2 = $this->_em->find(get_class($user1), $user2->id);

        $this->assertEquals(3, count($user2->groups));
        $this->assertEquals(3, count($user1->groups));
    }
}
