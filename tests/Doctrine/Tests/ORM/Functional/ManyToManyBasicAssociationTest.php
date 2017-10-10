<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsTag;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Basic many-to-many association tests.
 * ("Working with associations")
 *
 * @author robo
 */
class ManyToManyBasicAssociationTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testUnsetManyToMany()
    {
        $user = $this->addCmsUserGblancoWithGroups(1);

        unset($user->groups[0]->users[0]); // inverse side
        unset($user->groups[0]); // owning side!

        $this->_em->flush();

        // Check that the link in the association table has been deleted
        self::assertGblancoGroupCountIs(0);
    }

    public function testBasicManyToManyJoin()
    {
        $user = $this->addCmsUserGblancoWithGroups(1);
        $this->_em->clear();

        self::assertEquals(0, $this->_em->getUnitOfWork()->size());

        $query = $this->_em->createQuery("select u, g from Doctrine\Tests\Models\CMS\CmsUser u join u.groups g");

        $result = $query->getResult();

        self::assertEquals(2, $this->_em->getUnitOfWork()->size());
        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertEquals('Guilherme', $result[0]->name);
        self::assertEquals(1, $result[0]->getGroups()->count());
        $groups = $result[0]->getGroups();
        self::assertEquals('Developers_0', $groups[0]->getName());

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($result[0]));
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->_em->getUnitOfWork()->getEntityState($groups[0]));

        self::assertInstanceOf(PersistentCollection::class, $groups);
        self::assertInstanceOf(PersistentCollection::class, $groups[0]->getUsers());

        $groups[0]->getUsers()->clear();
        $groups->clear();

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery("select u, g from Doctrine\Tests\Models\CMS\CmsUser u join u.groups g");
        self::assertEquals(0, count($query->getResult()));
    }

    public function testManyToManyAddRemove()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $this->_em->clear();

        $uRep = $this->_em->getRepository(get_class($user));

        // Get user
        $user = $uRep->findOneById($user->getId());

        self::assertNotNull($user, "Has to return exactly one entry.");

        self::assertFalse($user->getGroups()->isInitialized());

        // Check groups
        self::assertEquals(2, $user->getGroups()->count());

        self::assertTrue($user->getGroups()->isInitialized());

        // Remove first group
        unset($user->groups[0]);
        //$user->getGroups()->remove(0);

        $this->_em->flush();
        $this->_em->clear();

        // Reload same user
        $user2 = $uRep->findOneById($user->getId());

        // Check groups
        self::assertEquals(1, $user2->getGroups()->count());
    }

    public function testManyToManyInverseSideIgnored()
    {
        $user = $this->addCmsUserGblancoWithGroups(0);

        $group = new CmsGroup;
        $group->name = 'Humans';

        // modify directly, addUser() would also (properly) set the owning side
        $group->users[] = $user;

        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();
        $this->_em->clear();

        // Association should not exist
        $user2 = $this->_em->find(get_class($user), $user->getId());

        self::assertNotNull($user2, "Has to return exactly one entry.");
        self::assertEquals(0, $user2->getGroups()->count());
    }

    public function testManyToManyCollectionClearing()
    {
        $user = $this->addCmsUserGblancoWithGroups($groupCount = 10);

        // Check that there are indeed 10 links in the association table
        self::assertGblancoGroupCountIs($groupCount);

        $user->groups->clear();

        $this->_em->flush();

        // Check that the links in the association table have been deleted
        self::assertGblancoGroupCountIs(0);
    }

    public function testManyToManyCollectionClearAndAdd()
    {
        $user = $this->addCmsUserGblancoWithGroups($groupCount = 10);

        $groups = $user->groups->toArray();
        $user->groups->clear();

        foreach ($groups AS $group) {
            $user->groups[] = $group;
        }

        self::assertInstanceOf(PersistentCollection::class, $user->groups);
        self::assertTrue($user->groups->isDirty());

        self::assertEquals($groupCount, count($user->groups), "There should be 10 groups in the collection.");

        $this->_em->flush();

        self::assertGblancoGroupCountIs($groupCount);
    }

    /**
     * @param int $expectedGroupCount
     */
    public function assertGblancoGroupCountIs($expectedGroupCount)
    {
        $countDql = "SELECT count(g.id) FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.groups g WHERE u.username = 'gblanco'";
        self::assertEquals(
            $expectedGroupCount,
            $this->_em->createQuery($countDql)->getSingleScalarResult(),
            "Failed to verify that CmsUser with username 'gblanco' has a group count of 10 with a DQL count query."
        );
    }

    public function testRetrieveManyToManyAndAddMore()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);

        $group = new CmsGroup();
        $group->name = 'Developers_Fresh';
        $this->_em->persist($group);
        $this->_em->flush();

        $this->_em->clear();

        /* @var $freshUser CmsUser */
        $freshUser = $this->_em->find(CmsUser::class, $user->getId());
        $newGroup = new CmsGroup();
        $newGroup->setName('12Monkeys');
        $freshUser->addGroup($newGroup);

        self::assertFalse($freshUser->groups->isInitialized(), "CmsUser::groups Collection has to be uninitialized for this test.");

        $this->_em->flush();

        self::assertFalse($freshUser->groups->isInitialized(), "CmsUser::groups Collection has to be uninitialized for this test.");
        self::assertEquals(3, count($freshUser->getGroups()));
        self::assertEquals(3, count($freshUser->getGroups()->getSnapshot()), "Snapshot of CmsUser::groups should contain 3 entries.");

        $this->_em->clear();

        $freshUser = $this->_em->find(CmsUser::class, $user->getId());
        self::assertEquals(3, count($freshUser->getGroups()));
    }

    /**
     * @group DDC-130
     */
    public function testRemoveUserWithManyGroups()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $userId = $user->getId();

        $this->_em->remove($user);
        $this->_em->flush();

        $newUser = $this->_em->find(get_class($user), $userId);
        self::assertNull($newUser);
    }

    /**
     * @group DDC-130
     */
    public function testRemoveGroupWithUser()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);

        foreach ($user->getGroups() AS $group) {
            $this->_em->remove($group);
        }
        $this->_em->flush();
        $this->_em->clear();

        $newUser = $this->_em->find(get_class($user), $user->getId());
        self::assertEquals(0, count($newUser->getGroups()));
    }

    public function testDereferenceCollectionDelete()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $user->groups = null;

        $this->_em->flush();
        $this->_em->clear();

        $newUser = $this->_em->find(get_class($user), $user->getId());
        self::assertEquals(0, count($newUser->getGroups()));
    }

    /**
     * @group DDC-839
     */
    public function testWorkWithDqlHydratedEmptyCollection()
    {
        $user = $this->addCmsUserGblancoWithGroups(0);
        $group = new CmsGroup();
        $group->name = "Developers0";
        $this->_em->persist($group);

        $this->_em->flush();
        $this->_em->clear();

        $newUser = $this->_em->createQuery('SELECT u, g FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.groups g WHERE u.id = ?1')
                             ->setParameter(1, $user->getId())
                             ->getSingleResult();
        self::assertEquals(0, count($newUser->groups));
        self::assertInternalType('array', $newUser->groups->getMapping());

        $newUser->addGroup($group);

        $this->_em->flush();
        $this->_em->clear();

        $newUser = $this->_em->find(get_class($user), $user->getId());
        self::assertEquals(1, count($newUser->groups));
    }

    /**
     * @param  int $groupCount
     * @return CmsUser
     */
    public function addCmsUserGblancoWithGroups($groupCount = 1)
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        for ($i=0; $i < $groupCount; ++$i) {
            $group = new CmsGroup;
            $group->name = 'Developers_' . $i;
            $user->addGroup($group);
        }

        $this->_em->persist($user);
        $this->_em->flush();

        self::assertNotNull($user->getId(), "User 'gblanco' should have an ID assigned after the persist()/flush() operation.");

        return $user;
    }

    /**
     * @group DDC-978
     */
    public function testClearAndResetCollection()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $group1 = new CmsGroup;
        $group1->name = 'Developers_New1';
        $group2 = new CmsGroup;
        $group2->name = 'Developers_New2';

        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $coll = new ArrayCollection([$group1, $group2]);
        $user->groups = $coll;
        $this->_em->flush();
        self::assertInstanceOf(PersistentCollection::class, $user->groups,
            "UnitOfWork should have replaced ArrayCollection with PersistentCollection.");
        $this->_em->flush();

        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);
        self::assertEquals(2, count($user->groups));
        self::assertEquals('Developers_New1', $user->groups[0]->name);
        self::assertEquals('Developers_New2', $user->groups[1]->name);
    }

    /**
     * @group DDC-733
     */
    public function testInitializePersistentCollection()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        self::assertFalse($user->groups->isInitialized(), "Pre-condition: lazy collection");
        $this->_em->getUnitOfWork()->initializeObject($user->groups);
        self::assertTrue($user->groups->isInitialized(), "Collection should be initialized after calling UnitOfWork::initializeObject()");
    }

    /**
     * @group DDC-1189
     * @group DDC-956
     */
    public function testClearBeforeLazyLoad()
    {
        $user = $this->addCmsUserGblancoWithGroups(4);

        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);
        $user->groups->clear();
        self::assertEquals(0, count($user->groups));

        $this->_em->flush();

        $user = $this->_em->find(get_class($user), $user->id);
        self::assertEquals(0, count($user->groups));
    }

    /**
     * @group DDC-3952
     */
    public function testManyToManyOrderByIsNotIgnored()
    {
        $user = $this->addCmsUserGblancoWithGroups(1);

        $group1 = new CmsGroup;
        $group2 = new CmsGroup;
        $group3 = new CmsGroup;

        $group1->name = 'C';
        $group2->name = 'A';
        $group3->name = 'B';

        $user->addGroup($group1);
        $user->addGroup($group2);
        $user->addGroup($group3);

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $criteria = Criteria::create()
            ->orderBy(['name' => Criteria::ASC]);

        self::assertEquals(
            ['A', 'B', 'C', 'Developers_0'],
            $user
                ->getGroups()
                ->matching($criteria)
                ->map(function (CmsGroup $group) {
                    return $group->getName();
                })
                ->toArray()
        );
    }

    /**
     * @group DDC-3952
     */
    public function testManyToManyOrderByHonorsFieldNameColumnNameAliases()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $tag1 = new CmsTag;
        $tag2 = new CmsTag;
        $tag3 = new CmsTag;

        $tag1->name = 'C';
        $tag2->name = 'A';
        $tag3->name = 'B';

        $user->addTag($tag1);
        $user->addTag($tag2);
        $user->addTag($tag3);

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $criteria = Criteria::create()
            ->orderBy(['name' => Criteria::ASC]);

        self::assertEquals(
            ['A', 'B', 'C'],
            $user
                ->getTags()
                ->matching($criteria)
                ->map(function (CmsTag $tag) {
                    return $tag->getName();
                })
                ->toArray()
        );
    }

    public function testMatchingWithLimit()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $groups = $user->groups;
        self::assertFalse($user->groups->isInitialized(), "Pre-condition: lazy collection");

        $criteria = Criteria::create()->setMaxResults(1);
        $result   = $groups->matching($criteria);

        self::assertCount(1, $result);

        self::assertFalse($user->groups->isInitialized(), "Post-condition: matching does not initialize collection");
    }

    public function testMatchingWithOffset()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $groups = $user->groups;
        self::assertFalse($user->groups->isInitialized(), "Pre-condition: lazy collection");

        $criteria = Criteria::create()->setFirstResult(1);
        $result   = $groups->matching($criteria);

        self::assertCount(1, $result);

        $firstGroup = $result->first();
        self::assertEquals('Developers_1', $firstGroup->name);

        self::assertFalse($user->groups->isInitialized(), "Post-condition: matching does not initialize collection");
    }

    public function testMatchingWithLimitAndOffset()
    {
        $user = $this->addCmsUserGblancoWithGroups(5);
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $groups = $user->groups;
        self::assertFalse($user->groups->isInitialized(), "Pre-condition: lazy collection");

        $criteria = Criteria::create()->setFirstResult(1)->setMaxResults(3);
        $result   = $groups->matching($criteria);

        self::assertCount(3, $result);

        $firstGroup = $result->first();
        self::assertEquals('Developers_1', $firstGroup->name);

        $lastGroup = $result->last();
        self::assertEquals('Developers_3', $lastGroup->name);

        self::assertFalse($user->groups->isInitialized(), "Post-condition: matching does not initialize collection");
    }

    public function testMatching()
    {
        $user = $this->addCmsUserGblancoWithGroups(2);
        $this->_em->clear();

        $user = $this->_em->find(get_class($user), $user->id);

        $groups = $user->groups;
        self::assertFalse($user->groups->isInitialized(), "Pre-condition: lazy collection");

        $criteria = Criteria::create()->where(Criteria::expr()->eq('name', (string) 'Developers_0'));
        $result   = $groups->matching($criteria);

        self::assertCount(1, $result);

        $firstGroup = $result->first();
        self::assertEquals('Developers_0', $firstGroup->name);

        self::assertFalse($user->groups->isInitialized(), "Post-condition: matching does not initialize collection");
    }
}
