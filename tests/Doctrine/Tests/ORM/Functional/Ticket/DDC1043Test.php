<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1043
 */
class DDC1043Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testChangeSetPlusWeirdPHPCastingIntCastingRule()
    {
        $user = new CmsUser();
        $user->name = "John Galt";
        $user->username = "jgalt";
        $user->status = "+44";

        $this->_em->persist($user);
        $this->_em->flush();

        $user->status = "44";
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(CmsUser::class, $user->id);
        $this->assertSame("44", $user->status);
    }
}
