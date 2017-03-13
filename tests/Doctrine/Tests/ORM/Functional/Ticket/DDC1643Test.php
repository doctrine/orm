<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Util\Debug;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;

/**
 * @group DDC-1643
 */
class DDC1643Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $user1;
    private $user2;

    public function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();

        $user1 = new CmsUser();
        $user1->username = "beberlei";
        $user1->name = "Benjamin";
        $user1->status = "active";
        $group1 = new CmsGroup();
        $group1->name = "test";
        $group2 = new CmsGroup();
        $group2->name = "test";
        $user1->addGroup($group1);
        $user1->addGroup($group2);
        $user2 = new CmsUser();
        $user2->username = "romanb";
        $user2->name = "Roman";
        $user2->status = "active";

        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->persist($group1);
        $this->em->persist($group2);
        $this->em->flush();
        $this->em->clear();

        $this->user1 = $this->em->find(get_class($user1), $user1->id);
        $this->user2 = $this->em->find(get_class($user1), $user2->id);
    }

    public function testClonePersistentCollectionAndReuse()
    {
        $user1 = $this->user1;

        $user1->groups = clone $user1->groups;

        $this->em->flush();
        $this->em->clear();

        $user1 = $this->em->find(get_class($user1), $user1->id);

        self::assertEquals(2, count($user1->groups));
    }

    public function testClonePersistentCollectionAndShare()
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $user2->groups = clone $user1->groups;

        $this->em->flush();
        $this->em->clear();

        $user1 = $this->em->find(get_class($user1), $user1->id);
        $user2 = $this->em->find(get_class($user1), $user2->id);

        self::assertEquals(2, count($user1->groups));
        self::assertEquals(2, count($user2->groups));
    }

    public function testCloneThenDirtyPersistentCollection()
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3 = new CmsGroup();
        $group3->name = "test";
        $user2->groups = clone $user1->groups;
        $user2->groups->add($group3);

        $this->em->persist($group3);
        $this->em->flush();
        $this->em->clear();

        $user1 = $this->em->find(get_class($user1), $user1->id);
        $user2 = $this->em->find(get_class($user1), $user2->id);

        self::assertEquals(3, count($user2->groups));
        self::assertEquals(2, count($user1->groups));
    }

    public function testNotCloneAndPassAroundFlush()
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $group3 = new CmsGroup();
        $group3->name = "test";
        $user2->groups = $user1->groups;
        $user2->groups->add($group3);

        self::assertCount(1, $user1->groups->getInsertDiff());

        $this->em->persist($group3);
        $this->em->flush();
        $this->em->clear();

        $user1 = $this->em->find(get_class($user1), $user1->id);
        $user2 = $this->em->find(get_class($user1), $user2->id);

        self::assertEquals(3, count($user2->groups));
        self::assertEquals(3, count($user1->groups));
    }
}

