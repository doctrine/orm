<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

class DDC849Test extends OrmFunctionalTestCase
{
    /** @var CmsUser */
    private $user;

    /** @var CmsGroup */
    private $group1;

    /** @var CmsGroup */
    private $group2;

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();

        $this->user           = new CmsUser();
        $this->user->username = 'beberlei';
        $this->user->name     = 'Benjamin';
        $this->user->status   = 'active';

        $this->group1       = new CmsGroup();
        $this->group1->name = 'Group 1';
        $this->group2       = new CmsGroup();
        $this->group2->name = 'Group 2';

        $this->user->addGroup($this->group1);
        $this->user->addGroup($this->group2);

        $this->_em->persist($this->user);
        $this->_em->persist($this->group1);
        $this->_em->persist($this->group2);

        $this->_em->flush();
        $this->_em->clear();

        $this->user = $this->_em->find(CmsUser::class, $this->user->getId());
    }

    public function testRemoveContains(): void
    {
        $group1 = $this->user->groups[0];
        $group2 = $this->user->groups[1];

        $this->assertTrue($this->user->groups->contains($group1));
        $this->assertTrue($this->user->groups->contains($group2));

        $this->user->groups->removeElement($group1);
        $this->user->groups->remove(1);

        $this->assertFalse($this->user->groups->contains($group1));
        $this->assertFalse($this->user->groups->contains($group2));
    }

    public function testClearCount(): void
    {
        $this->user->addGroup(new CmsGroup());
        $this->assertEquals(3, count($this->user->groups));

        $this->user->groups->clear();

        $this->assertEquals(0, $this->user->groups->count());
        $this->assertEquals(0, count($this->user->groups));
    }

    public function testClearContains(): void
    {
        $group1 = $this->user->groups[0];
        $group2 = $this->user->groups[1];

        $this->assertTrue($this->user->groups->contains($group1));
        $this->assertTrue($this->user->groups->contains($group2));

        $this->user->groups->clear();

        $this->assertFalse($this->user->groups->contains($group1));
        $this->assertFalse($this->user->groups->contains($group2));
    }
}
