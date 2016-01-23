<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\Collections\ArrayCollection;
use Shitty\Tests\Models\CMS\CmsUser;
use Shitty\Tests\Models\CMS\CmsGroup;

/**
 * @group DDC-1276
 */
class DDC1276Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue()
    {
        $user = new CmsUser();
        $user->name = "Benjamin";
        $user->username = "beberlei";
        $user->status = "active";
        $this->_em->persist($user);

        for ($i = 0; $i < 2; $i++) {
            $group = new CmsGroup();
            $group->name = "group".$i;
            $user->groups[] = $group;
            $this->_em->persist($group);
        }
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $cloned = clone $user;

        $this->assertSame($user->groups, $cloned->groups);
        $this->assertEquals(2, count($user->groups));
        $this->_em->merge($cloned);

        $this->assertEquals(2, count($user->groups));

        $this->_em->flush();
    }
}
