<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-1594
 */
class DDC1594Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    use VerifyDeprecations;

    public function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue()
    {
        $user = new CmsUser();
        $user->status = 'foo';
        $user->username = 'foo';
        $user->name = 'foo';

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();
        $detachedUser = clone $user;
        $detachedUser->name = 'bar';
        $detachedUser->status = 'bar';

        $newUser = $this->_em->getReference(get_class($user), $user->id);

        $mergedUser = $this->_em->merge($detachedUser);

        $this->assertNotSame($mergedUser, $detachedUser);
        $this->assertEquals('bar', $detachedUser->getName());
        $this->assertEquals('bar', $mergedUser->getName());
        $this->assertHasDeprecationMessages();
    }
}
