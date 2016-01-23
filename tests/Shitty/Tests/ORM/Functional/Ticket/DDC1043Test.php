<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @group DDC-1043
 */
class DDC1043Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testChangeSetPlusWeirdPHPCastingIntCastingRule()
    {
        $user = new \Shitty\Tests\Models\CMS\CmsUser();
        $user->name = "John Galt";
        $user->username = "jgalt";
        $user->status = "+44";

        $this->_em->persist($user);
        $this->_em->flush();

        $user->status = "44";
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find("Doctrine\Tests\Models\CMS\CmsUser", $user->id);
        $this->assertSame("44", $user->status);
    }
}
