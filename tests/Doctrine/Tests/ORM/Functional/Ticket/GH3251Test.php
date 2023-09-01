<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH3251Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testAddingToUninitializedPersistentCollection(): void
    {
        $user           = new CmsUser();
        $user->username = 'test';
        $user->name     = 'test';

        $group1       = new CmsGroup();
        $group1->name = 'group1';
        $user->addGroup($group1);

        $this->_em->persist($user);
        $this->_em->persist($group1);
        $this->_em->flush();
        $this->_em->clear();

        $userRefetched = $this->_em->find(CmsUser::class, $user->id);

        // The groups collection is not (yet) initialized
        self::assertFalse($userRefetched->getGroups()->isInitialized());

        // Lazy collection (\Doctrine\ORM\PersistentCollection::doInitialize)
        // can deal with add() calls without having to initialize
        $group2       = new CmsGroup();
        $group2->name = 'group2';
        $userRefetched->addGroup($group2);
        self::assertFalse($userRefetched->getGroups()->isInitialized());

        // Now this will initialize:
        self::assertCount(2, $userRefetched->getGroups());
        self::assertTrue($userRefetched->getGroups()->isInitialized());

        // Persistent and new elements have been merged
        self::assertSame($group1->id, $userRefetched->getGroups()->get(0)->id);
        self::assertSame($group2->id, $userRefetched->getGroups()->get(1)->id);
    }

    public function testInitializeCollectionThroughFetchJoin(): void
    {
        $user           = new CmsUser();
        $user->username = 'test';
        $user->name     = 'test';

        $group1       = new CmsGroup();
        $group1->name = 'group1';
        $user->addGroup($group1);

        $this->_em->persist($user);
        $this->_em->persist($group1);
        $this->_em->flush();
        $this->_em->clear(); // necessary to make bug surface

        $userRefetched = $this->_em->find(CmsUser::class, $user->id);

        // The groups collection is not (yet) initialized
        self::assertFalse($userRefetched->getGroups()->isInitialized());

        // Add new element, which still does not need to initialize
        $group2       = new CmsGroup();
        $group2->name = 'group2';
        $userRefetched->addGroup($group2);

        self::assertFalse($userRefetched->getGroups()->isInitialized());

        // Fetch-join query will go through
        // \Doctrine\ORM\Internal\Hydration\ObjectHydrator::initRelatedCollection,
        // which does not take care of newly added elements (that's a bug!)
        $this->_em->createQuery('SELECT u, g FROM ' . CmsUser::class . ' u JOIN u.groups g')->getResult();

        self::assertTrue($userRefetched->getGroups()->isInitialized());

        // Persistent and new elements have been merged
        self::assertCount(2, $userRefetched->getGroups());
        self::assertSame($group1->id, $userRefetched->getGroups()->get(0)->id);
        self::assertSame($group2->id, $userRefetched->getGroups()->get(1)->id);
    }
}
