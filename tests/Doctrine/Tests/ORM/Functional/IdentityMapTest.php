<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function get_class;

/**
 * IdentityMapTest
 *
 * Tests correct behavior and usage of the identity map. Local values and associations
 * that are already fetched always prevail, unless explicitly refreshed.
 */
class IdentityMapTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testBasicIdentityManagement(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = 1234;
        $address->city    = 'Berlin';

        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user2 = $this->_em->find(get_class($user), $user->getId());
        self::assertNotSame($user2, $user);
        $user3 = $this->_em->find(get_class($user), $user->getId());
        self::assertSame($user2, $user3);

        $address2 = $this->_em->find(get_class($address), $address->getId());
        self::assertNotSame($address2, $address);
        $address3 = $this->_em->find(get_class($address), $address->getId());
        self::assertSame($address2, $address3);

        self::assertSame($user2->getAddress(), $address2); // !!!
    }

    public function testSingleValuedAssociationIdentityMapBehaviorWithRefresh(): void
    {
        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = '12345';
        $address->city    = 'Berlin';

        $user1           = new CmsUser();
        $user1->status   = 'dev';
        $user1->username = 'romanb';
        $user1->name     = 'Roman B.';

        $user2           = new CmsUser();
        $user2->status   = 'dev';
        $user2->username = 'gblanco';
        $user2->name     = 'Guilherme Blanco';

        $address->setUser($user1);

        $this->_em->persist($address);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        self::assertSame($user1, $address->user);

        //external update to CmsAddress
        $this->_em->getConnection()->executeStatement('update cms_addresses set user_id = ?', [$user2->getId()]);

        // But we want to have this external change!
        // Solution 1: refresh(), broken atm!
        $this->_em->refresh($address);

        // Now the association should be "correct", referencing $user2
        self::assertSame($user2, $address->user);
        self::assertSame($user2->address, $address); // check back reference also

        // Attention! refreshes can result in broken bidirectional associations! this is currently expected!
        // $user1 still points to $address!
        self::assertSame($user1->address, $address);
    }

    public function testSingleValuedAssociationIdentityMapBehaviorWithRefreshQuery(): void
    {
        $address          = new CmsAddress();
        $address->country = 'de';
        $address->zip     = '12345';
        $address->city    = 'Berlin';

        $user1           = new CmsUser();
        $user1->status   = 'dev';
        $user1->username = 'romanb';
        $user1->name     = 'Roman B.';

        $user2           = new CmsUser();
        $user2->status   = 'dev';
        $user2->username = 'gblanco';
        $user2->name     = 'Guilherme Blanco';

        $address->setUser($user1);

        $this->_em->persist($address);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        self::assertSame($user1, $address->user);

        //external update to CmsAddress
        $this->_em->getConnection()->executeStatement('update cms_addresses set user_id = ?', [$user2->getId()]);

        //select
        $q        = $this->_em->createQuery('select a, u from Doctrine\Tests\Models\CMS\CmsAddress a join a.user u');
        $address2 = $q->getSingleResult();

        self::assertSame($address, $address2);

        // Should still be $user1
        self::assertSame($user1, $address2->user);
        self::assertNull($user2->address);

        // But we want to have this external change!
        // Solution 2: Alternatively, a refresh query should work
        $q = $this->_em->createQuery('select a, u from Doctrine\Tests\Models\CMS\CmsAddress a join a.user u');
        $q->setHint(Query::HINT_REFRESH, true);
        $address3 = $q->getSingleResult();

        self::assertSame($address, $address3); // should still be the same, always from identity map

        // Now the association should be "correct", referencing $user2
        self::assertSame($user2, $address2->user);
        self::assertSame($user2->address, $address2); // check back reference also

        // Attention! refreshes can result in broken bidirectional associations! this is currently expected!
        // $user1 still points to $address2!
        self::assertSame($user1->address, $address2);
    }

    public function testCollectionValuedAssociationIdentityMapBehaviorWithRefreshQuery(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $phone1              = new CmsPhonenumber();
        $phone1->phonenumber = 123;

        $phone2              = new CmsPhonenumber();
        $phone2->phonenumber = 234;

        $phone3              = new CmsPhonenumber();
        $phone3->phonenumber = 345;

        $user->addPhonenumber($phone1);
        $user->addPhonenumber($phone2);
        $user->addPhonenumber($phone3);

        $this->_em->persist($user); // cascaded to phone numbers
        $this->_em->flush();

        self::assertCount(3, $user->getPhonenumbers());
        self::assertFalse($user->getPhonenumbers()->isDirty());

        //external update to CmsAddress
        $this->_em->getConnection()->executeStatement('insert into cms_phonenumbers (phonenumber, user_id) VALUES (?,?)', [999, $user->getId()]);

        //select
        $q     = $this->_em->createQuery('select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');
        $user2 = $q->getSingleResult();

        self::assertSame($user, $user2);

        // Should still be the same 3 phonenumbers
        self::assertCount(3, $user2->getPhonenumbers());

        // But we want to have this external change!
        // Solution 1: refresh().
        //$this->_em->refresh($user2); broken atm!
        // Solution 2: Alternatively, a refresh query should work
        $q = $this->_em->createQuery('select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');
        $q->setHint(Query::HINT_REFRESH, true);
        $user3 = $q->getSingleResult();

        self::assertSame($user, $user3); // should still be the same, always from identity map

        // Now the collection should be refreshed with correct count
        self::assertCount(4, $user3->getPhonenumbers());
    }

    /** @group non-cacheable */
    public function testCollectionValuedAssociationIdentityMapBehaviorWithRefresh(): void
    {
        $user           = new CmsUser();
        $user->status   = 'dev';
        $user->username = 'romanb';
        $user->name     = 'Roman B.';

        $phone1              = new CmsPhonenumber();
        $phone1->phonenumber = 123;

        $phone2              = new CmsPhonenumber();
        $phone2->phonenumber = 234;

        $phone3              = new CmsPhonenumber();
        $phone3->phonenumber = 345;

        $user->addPhonenumber($phone1);
        $user->addPhonenumber($phone2);
        $user->addPhonenumber($phone3);

        $this->_em->persist($user); // cascaded to phone numbers
        $this->_em->flush();

        self::assertCount(3, $user->getPhonenumbers());

        //external update to CmsAddress
        $this->_em->getConnection()->executeStatement('insert into cms_phonenumbers (phonenumber, user_id) VALUES (?,?)', [999, $user->getId()]);

        //select
        $q     = $this->_em->createQuery('select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');
        $user2 = $q->getSingleResult();

        self::assertSame($user, $user2);

        // Should still be the same 3 phonenumbers
        self::assertCount(3, $user2->getPhonenumbers());

        // But we want to have this external change!
        // Solution 1: refresh().
        $this->_em->refresh($user2);

        self::assertSame($user, $user2); // should still be the same, always from identity map

        // Now the collection should be refreshed with correct count
        self::assertCount(4, $user2->getPhonenumbers());
    }
}
