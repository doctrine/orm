<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Tests\Models\CMS\CmsUser;
use Shitty\Tests\Models\CMS\CmsEmail;

/**
 * @group DDC-1666
 */
class DDC1666Test extends \Shitty\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testGivenOrphanRemovalOneToOne_WhenReplacing_ThenNoUniqueConstraintError()
    {
        $user = new CmsUser();
        $user->name = "Benjamin";
        $user->username = "beberlei";
        $user->status = "something";
        $user->setEmail($email = new CmsEmail());
        $email->setEmail("kontakt@beberlei.de");

        $this->_em->persist($user);
        $this->_em->flush();

        $this->assertTrue($this->_em->contains($email));

        $user->setEmail($newEmail = new CmsEmail());
        $newEmail->setEmail("benjamin.eberlei@googlemail.com");

        $this->_em->flush();

        $this->assertFalse($this->_em->contains($email));
    }
}
