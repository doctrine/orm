<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

class DDC758Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        self::markTestSkipped('Destroys testsuite');
        $this->useModelSet('cms');

        parent::setUp();
    }

    /**
     * Helper method to set cascade to merge only
     */
    private function setCascadeMergeFor($class): void
    {
        $metadata = $this->_em->getMetadataFactory()->getMetadataFor($class);
        foreach ($metadata->associationMappings as $key => $associationMapping) {
            $metadata->associationMappings[$key]['isCascadePersist'] = false;
            $metadata->associationMappings[$key]['isCascadeMerge']   = true;
            $metadata->associationMappings[$key]['isCascadeRemove']  = false;
            $metadata->associationMappings[$key]['isCascadeDetach']  = false;
        }
    }

    /**
     * Test that changing associations on detached entities and then cascade merging them
     * causes the database to be updated with the new associations.
     * This specifically tests adding new associations.
     */
    public function testManyToManyMergeAssociationAdds(): void
    {
        $this->setCascadeMergeFor(CmsUser::class);
        $this->setCascadeMergeFor(CmsGroup::class);

        // Put entities in the database
        $cmsUser           = new CmsUser();
        $cmsUser->username = 'dave';
        $cmsUser->name     = 'Dave Keen';
        $cmsUser->status   = 'testing';

        $group1       = new CmsGroup();
        $group1->name = 'Group 1';

        $group2       = new CmsGroup();
        $group2->name = 'Group 2';

        $this->_em->persist($cmsUser);
        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->flush();

        $cmsUserId = $cmsUser->id;
        $group1Id  = $group1->id;
        $group2Id  = $group2->id;

        $this->_em->clear();

        // Now create detached versions of the entities with some new associations.
        $cmsUser           = new CmsUser();
        $cmsUser->id       = $cmsUserId;
        $cmsUser->username = 'dave';
        $cmsUser->name     = 'Dave Keen';
        $cmsUser->status   = 'testing';
        $cmsUser->groups   = new ArrayCollection();

        $group1        = new CmsGroup();
        $group1->id    = $group1Id;
        $group1->name  = 'Group 1';
        $group1->users = new ArrayCollection();

        $group2        = new CmsGroup();
        $group2->id    = $group2Id;
        $group2->name  = 'Group 2';
        $group2->users = new ArrayCollection();

        $cmsUser->addGroup($group1);
        $cmsUser->addGroup($group2);

        // Cascade merge of cmsUser followed by a flush should add in the bidirectional new many-to-many associations between the user and the groups
        $this->_em->merge($cmsUser);
        $this->_em->flush();

        $this->_em->clear();

        $cmsUsers  = $this->_em->getRepository(CmsUser::class)->findAll();
        $cmsGroups = $this->_em->getRepository(CmsGroup::class)->findAll();

        // Check the entities are in the database
        self::assertEquals(1, count($cmsUsers));
        self::assertEquals(2, count($cmsGroups));

        // Check the associations between the entities are now in the database
        self::assertEquals(2, count($cmsUsers[0]->groups));
        self::assertEquals(1, count($cmsGroups[0]->users));
        self::assertEquals(1, count($cmsGroups[1]->users));

        self::assertSame($cmsUsers[0]->groups[0], $cmsGroups[0]);
        self::assertSame($cmsUsers[0]->groups[1], $cmsGroups[1]);
        self::assertSame($cmsGroups[0]->users[0], $cmsUsers[0]);
        self::assertSame($cmsGroups[1]->users[0], $cmsUsers[0]);
    }

    /**
     * Test that changing associations on detached entities and then cascade merging them causes the
     * database to be updated with the new associations.
     */
    public function testManyToManyMergeAssociationRemoves(): void
    {
        $this->setCascadeMergeFor(CmsUser::class);
        $this->setCascadeMergeFor(CmsGroup::class);

        $cmsUser           = new CmsUser();
        $cmsUser->username = 'dave';
        $cmsUser->name     = 'Dave Keen';
        $cmsUser->status   = 'testing';

        $group1       = new CmsGroup();
        $group1->name = 'Group 1';

        $group2       = new CmsGroup();
        $group2->name = 'Group 2';

        $cmsUser->addGroup($group1);
        $cmsUser->addGroup($group2);

        $this->_em->persist($cmsUser);
        $this->_em->persist($group1);
        $this->_em->persist($group2);
        $this->_em->flush();

        $cmsUserId = $cmsUser->id;
        $group1Id  = $group1->id;
        $group2Id  = $group2->id;

        $this->_em->clear();

        // Now create detached versions of the entities with NO associations.
        $cmsUser           = new CmsUser();
        $cmsUser->id       = $cmsUserId;
        $cmsUser->username = 'dave';
        $cmsUser->name     = 'Dave Keen';
        $cmsUser->status   = 'testing';
        $cmsUser->groups   = new ArrayCollection();

        $group1        = new CmsGroup();
        $group1->id    = $group1Id;
        $group1->name  = 'Group 1';
        $group1->users = new ArrayCollection();

        $group2        = new CmsGroup();
        $group2->id    = $group2Id;
        $group2->name  = 'Group 2';
        $group2->users = new ArrayCollection();

        // Cascade merge of cmsUser followed by a flush should result in the association array collection being empty
        $this->_em->merge($cmsUser);
        $this->_em->flush();

        $this->_em->clear();

        $cmsUsers  = $this->_em->getRepository(CmsUser::class)->findAll();
        $cmsGroups = $this->_em->getRepository(CmsGroup::class)->findAll();

        // Check the entities are in the database
        self::assertEquals(1, count($cmsUsers));
        self::assertEquals(2, count($cmsGroups));

        // Check the associations between the entities are now in the database
        self::assertEquals(0, count($cmsUsers[0]->groups));
        self::assertEquals(0, count($cmsGroups[0]->users));
        self::assertEquals(0, count($cmsGroups[1]->users));
    }
}
