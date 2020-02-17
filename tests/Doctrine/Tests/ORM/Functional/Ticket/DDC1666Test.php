<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1666
 */
class DDC1666Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testGivenOrphanRemovalOneToOneWhenReplacingThenNoUniqueConstraintError() : void
    {
        $user                  = new CmsUser();
        $user->name            = 'Benjamin';
        $user->username        = 'beberlei';
        $user->status          = 'something';
        $user->setEmail($email = new CmsEmail());
        $email->setEmail('kontakt@beberlei.de');

        $this->em->persist($user);
        $this->em->flush();

        self::assertTrue($this->em->contains($email));

        $user->setEmail($newEmail = new CmsEmail());
        $newEmail->setEmail('benjamin.eberlei@googlemail.com');

        $this->em->flush();

        self::assertFalse($this->em->contains($email));
    }
}
