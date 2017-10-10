<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group DDC-1594
 */
class DDC1594Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
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

        self::assertNotSame($mergedUser, $detachedUser);
        self::assertEquals('bar', $detachedUser->getName());
        self::assertEquals('bar', $mergedUser->getName());
    }
}
