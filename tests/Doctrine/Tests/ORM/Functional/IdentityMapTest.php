<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser,
    Doctrine\Tests\Models\CMS\CmsAddress,
    Doctrine\Tests\Models\CMS\CmsPhonenumber,
    Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

/**
 * IdentityMapTest
 * 
 * Tests correct behavior and usage of the identity map. Local values and associations
 * that are already fetched always prevail, unless explicitly refreshed.
 *
 * @author Roman Borschel <roman@code-factory.org>
 */
class IdentityMapTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testSingleValuedAssociationIdentityMapBehavior()
    {
        $address = new CmsAddress;
        $address->country = 'de';
        $address->zip = '12345';
        $address->city = 'Berlin';
        
        $user1 = new CmsUser;
        $user1->status = 'dev';
        $user1->username = 'romanb';
        $user1->name = 'Roman B.';

        $user2 = new CmsUser;
        $user2->status = 'dev';
        $user2->username = 'gblanco';
        $user2->name = 'Guilherme Blanco';
        
        $address->setUser($user1);
        
        $this->_em->persist($address);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();
        
        
        $this->assertSame($user1, $address->user);
        
        //external update to CmsAddress
        $this->_em->getConnection()->executeUpdate('update cms_addresses set user_id = ?', array($user2->getId()));
        
        //select
        $q = $this->_em->createQuery('select a, u from Doctrine\Tests\Models\CMS\CmsAddress a join a.user u');
        $address2 = $q->getSingleResult();
        
        $this->assertSame($address, $address2);
        
        // Should still be $user1
        $this->assertSame($user1, $address2->user);
        $this->assertTrue($user2->address === null);
        
        // But we want to have this external change!
        // Solution 1: refresh(), broken atm!  
        //$this->_em->refresh($address2);
        // Solution 2: Alternatively, a refresh query should work
        $q = $this->_em->createQuery('select a, u from Doctrine\Tests\Models\CMS\CmsAddress a join a.user u');
        $q->setHint(Query::HINT_REFRESH, true);
        $address3 = $q->getSingleResult();
        
        $this->assertSame($address, $address3); // should still be the same, always from identity map
        
        // Now the association should be "correct", referencing $user2
        $this->assertSame($user2, $address2->user);
        $this->assertSame($user2->address, $address2); // check back reference also
        
        // Attention! refreshes can result in broken bidirectional associations! this is currently expected!
        // $user1 still points to $address2!
        $this->assertSame($user1->address, $address2);
    }
    
    public function testCollectionValuedAssociationIdentityMapBehavior()
    {
        $user = new CmsUser;
        $user->status = 'dev';
        $user->username = 'romanb';
        $user->name = 'Roman B.';

        $phone1 = new CmsPhonenumber;
        $phone1->phonenumber = 123;
        
        $phone2 = new CmsPhonenumber;
        $phone2->phonenumber = 234;
        
        $phone3 = new CmsPhonenumber;
        $phone3->phonenumber = 345;
        
        $user->addPhonenumber($phone1);
        $user->addPhonenumber($phone2);
        $user->addPhonenumber($phone3);
        
        $this->_em->persist($user); // cascaded to phone numbers
        $this->_em->flush();
        
        $this->assertEquals(3, count($user->getPhonenumbers()));
        
        //external update to CmsAddress
        $this->_em->getConnection()->executeUpdate('insert into cms_phonenumbers (phonenumber, user_id) VALUES (?,?)', array(999, $user->getId()));
        
        //select
        $q = $this->_em->createQuery('select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');
        $user2 = $q->getSingleResult();
        
        $this->assertSame($user, $user2);
        
        // Should still be the same 3 phonenumbers
        $this->assertEquals(3, count($user2->getPhonenumbers()));
        
        // But we want to have this external change!
        // Solution 1: refresh().
        //$this->_em->refresh($user2); broken atm!
        // Solution 2: Alternatively, a refresh query should work
        $q = $this->_em->createQuery('select u, p from Doctrine\Tests\Models\CMS\CmsUser u join u.phonenumbers p');
        $q->setHint(Query::HINT_REFRESH, true);
        $user3 = $q->getSingleResult();
        
        $this->assertSame($user, $user3); // should still be the same, always from identity map
        
        // Now the collection should be refreshed with correct count
        $this->assertEquals(4, count($user3->getPhonenumbers()));
    }
}

