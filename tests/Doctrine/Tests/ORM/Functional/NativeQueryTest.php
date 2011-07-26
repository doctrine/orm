<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyEmployee;

require_once __DIR__ . '/../../TestInit.php';

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NativeQueryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $platform = null;

    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
        $this->platform = $this->_em->getConnection()->getDatabasePlatform();
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
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');

        $query = $this->_em->createNativeQuery('SELECT id, name FROM cms_users WHERE username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Roman', $users[0]->name);
    }

    public function testBasicNativeQueryWithMetaResult()
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
        $rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsAddress', 'a');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');
        $rsm->addMetaResult('a', $this->platform->getSQLResultCasing('user_id'), 'user_id');

        $query = $this->_em->createNativeQuery('SELECT a.id, a.country, a.zip, a.city, a.user_id FROM cms_addresses a WHERE a.id = ?', $rsm);
        $query->setParameter(1, $addr->id);

        $addresses = $query->getResult();

        $this->assertEquals(1, count($addresses));
        $this->assertTrue($addresses[0] instanceof CmsAddress);
        $this->assertEquals($addr->country, $addresses[0]->country);
        $this->assertEquals($addr->zip, $addresses[0]->zip);
        $this->assertEquals($addr->city, $addresses[0]->city);
        $this->assertEquals($addr->street, $addresses[0]->street);
        $this->assertTrue($addresses[0]->user instanceof CmsUser);
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
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', $this->platform->getSQLResultCasing('phonenumber'), 'phonenumber');
        
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
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsAddress', 'a', 'u', 'address');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('a_id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');
        
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

    public function testJoinedOneToManyNativeQueryWithRSMBuilder()
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

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p', 'u', 'phonenumbers');
        $query = $this->_em->createNativeQuery('SELECT u.*, p.* FROM cms_users u LEFT JOIN cms_phonenumbers p ON u.id = p.user_id WHERE username = ?', $rsm);
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

        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsPhonenumber', 'p');
        $query = $this->_em->createNativeQuery('SELECT p.* FROM cms_phonenumbers p WHERE p.phonenumber = ?', $rsm);
        $query->setParameter(1, $phone->phonenumber);
        $phone = $query->getSingleResult();

        $this->assertNotNull($phone->getUser());
        $this->assertEquals($user->name, $phone->getUser()->getName());
    }

    public function testJoinedOneToOneNativeQueryWithRSMBuilder()
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


        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', 'a', 'u', 'address', array('id' => 'a_id'));

        $query = $this->_em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);
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

        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', 'a');
        $query = $this->_em->createNativeQuery('SELECT a.* FROM cms_addresses a WHERE a.id = ?', $rsm);
        $query->setParameter(1, $addr->getId());
        $address = $query->getSingleResult();

        $this->assertNotNull($address->getUser());
        $this->assertEquals($user->name, $address->getUser()->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRSMBuilderThrowsExceptionOnColumnConflict()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', 'a', 'u', 'address');
    }

    /**
     * @group PR-39
     */
    public function testUnknownParentAliasThrowsException()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $rsm->addJoinedEntityFromClassMetadata('Doctrine\Tests\Models\CMS\CmsAddress', 'a', 'un', 'address', array('id' => 'a_id'));

        $query = $this->_em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);
        $query->setParameter(1, 'romanb');

        $this->setExpectedException(
            "Doctrine\ORM\Internal\Hydration\HydrationException",
            "The parent object of entity result with alias 'a' was not found. The parent alias is 'un'."
        );
        $users = $query->getResult();
    }
}

