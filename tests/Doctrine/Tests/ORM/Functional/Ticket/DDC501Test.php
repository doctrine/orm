<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\VerifyDeprecations;

use function count;
use function serialize;
use function unserialize;

/**
 * ----------------- !! NOTE !! --------------------
 * To reproduce the manyToMany-Bug it's necessary
 * to cascade "merge" on cmUser::groups
 * -------------------------------------------------
 *
 * @PHP-Version 5.3.2
 * @PHPUnit-Version 3.4.11
 */
class DDC501Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testMergeUnitializedManyToManyAndOneToManyCollections(): void
    {
        // Create User
        $user = $this->createAndPersistUser();
        $this->_em->flush();

        $this->assertTrue($this->_em->contains($user));
        $this->_em->clear();
        $this->assertFalse($this->_em->contains($user));

        unset($user);

        // Reload User from DB *without* any associations (i.e. an uninitialized PersistantCollection)
        $userReloaded = $this->loadUserFromEntityManager();

        $this->assertTrue($this->_em->contains($userReloaded));
        $this->_em->clear();
        $this->assertFalse($this->_em->contains($userReloaded));

        // freeze and unfreeze
        $userClone = unserialize(serialize($userReloaded));
        $this->assertInstanceOf(CmsUser::class, $userClone);

        // detached user can't know about his phonenumbers
        $this->assertEquals(0, count($userClone->getPhonenumbers()));
        $this->assertFalse($userClone->getPhonenumbers()->isInitialized(), 'User::phonenumbers should not be marked initialized.');

        // detached user can't know about his groups either
        $this->assertEquals(0, count($userClone->getGroups()));
        $this->assertFalse($userClone->getGroups()->isInitialized(), 'User::groups should not be marked initialized.');

        // Merge back and flush
        $userClone = $this->_em->merge($userClone);

        // Back in managed world I would expect to have my phonenumbers back but they aren't!
    // Remember I didn't touch (and probably didn't need) them at all while in detached mode.
        $this->assertEquals(4, count($userClone->getPhonenumbers()), 'Phonenumbers are not available anymore');

        // This works fine as long as cmUser::groups doesn't cascade "merge"
        $this->assertEquals(2, count($userClone->getGroups()));

        $this->_em->flush();
        $this->_em->clear();

        $this->assertFalse($this->_em->contains($userClone));

        // Reload user from DB
        $userFromEntityManager = $this->loadUserFromEntityManager();

        //Strange: Now the phonenumbers are back again
        $this->assertEquals(4, count($userFromEntityManager->getPhonenumbers()));

        // This works fine as long as cmUser::groups doesn't cascade "merge"
        // Otherwise group memberships are physically deleted now!
        $this->assertEquals(2, count($userClone->getGroups()));
        $this->assertHasDeprecationMessages();
    }

    protected function createAndPersistUser(): CmsUser
    {
        $user           = new CmsUser();
        $user->name     = 'Luka';
        $user->username = 'lukacho';
        $user->status   = 'developer';

        foreach ([1111, 2222, 3333, 4444] as $number) {
            $phone              = new CmsPhonenumber();
            $phone->phonenumber = $number;
            $user->addPhonenumber($phone);
        }

        foreach (['Moshers', 'Headbangers'] as $groupName) {
            $group = new CmsGroup();
            $group->setName($groupName);
            $user->addGroup($group);
        }

        $this->_em->persist($user);

        return $user;
    }

    protected function loadUserFromEntityManager(): CmsUser
    {
        return $this->_em
                ->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name like :name')
                ->setParameter('name', 'Luka')
                ->getSingleResult();
    }
}
