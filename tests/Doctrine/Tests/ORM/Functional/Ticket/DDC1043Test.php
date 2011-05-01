<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1043
 */
class DDC1043Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testChangeSetPlusWeirdPHPCastingIntCastingRule()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
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