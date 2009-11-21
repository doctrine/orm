<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;

require_once __DIR__ . '/../../TestInit.php';

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NativeQueryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
        
        if ($this->_em->getConnection()->getDatabasePlatform()->getName() == 'oracle') {
            $this->markTestSkipped('The ' . __CLASS__ .' does not work with Oracle due to character casing.');
        }
    }

    public function testBasicNativeQuery()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();

        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');

        $query = $this->_em->createNativeQuery('SELECT id, name FROM cms_users WHERE username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
    }
    
    public function testJoinedOneToManyNativeQuery()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        
        $phone = new CmsPhonenumber;
        $phone->phonenumber = 424242;
        
        $user->addPhonenumber($phone);
        
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();
        
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'status', 'status');
        $rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', 'phonenumber', 'phonenumber');
        
        $query = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $users = $query->getResult();
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertTrue($users[0]->getPhonenumbers() instanceof \Doctrine\ORM\PersistentCollection);
        $this->assertTrue($users[0]->getPhonenumbers()->isInitialized());
        $this->assertEquals(1, count($users[0]->getPhonenumbers()));
        $phones = $users[0]->getPhonenumbers();
        $this->assertEquals(424242, $phones[0]->phonenumber);
        $this->assertTrue($phones[0]->getUser() === $users[0]);
        
    }
    
    public function testJoinedOneToOneNativeQuery()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        
        $addr = new CmsAddress;
        $addr->country = 'germany';
        $addr->zip = 10827;
        $addr->city = 'Berlin';
        
        
        $user->setAddress($addr);
        
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();
        
        
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');
        $rsm->addFieldResult('u', 'status', 'status');
        $rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsAddress', 'a', 'u', 'address');
        $rsm->addFieldResult('a', 'a_id', 'id');
        $rsm->addFieldResult('a', 'country', 'country');
        $rsm->addFieldResult('a', 'zip', 'zip');
        $rsm->addFieldResult('a', 'city', 'city');
        
        $query = $this->_em->createNativeQuery('SELECT u.id, u.name, u.status, a.id AS a_id, a.country, a.zip, a.city FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);
        $query->setParameter(1, 'romanb');
        
        $users = $query->getResult();
        
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertTrue($users[0]->getPhonenumbers() instanceof \Doctrine\ORM\PersistentCollection);
        $this->assertFalse($users[0]->getPhonenumbers()->isInitialized());
        $this->assertTrue($users[0]->getAddress() instanceof CmsAddress);
        $this->assertTrue($users[0]->getAddress()->getUser() == $users[0]);
        $this->assertEquals('germany', $users[0]->getAddress()->getCountry());
        $this->assertEquals(10827, $users[0]->getAddress()->getZipCode());
        $this->assertEquals('Berlin', $users[0]->getAddress()->getCity());
    }

    public function testFluentInterface()
    {
        $rsm = new ResultSetMapping;

        $q = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
        $q2 = $q->setSql('foo', $rsm)
          ->setResultSetMapping($rsm)
          ->expireResultCache(true)
          ->setHint('foo', 'bar')
          ->setParameter(1, 'foo')
          ->setParameters(array(2 => 'bar'))
          ->setResultCacheDriver(null)
          ->setResultCacheLifetime(3500);

        $this->assertSame($q, $q2);
    }
}

