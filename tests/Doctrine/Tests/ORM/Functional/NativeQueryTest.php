<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\Parameter;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyPerson;

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
        $this->useModelSet('company');
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
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $users[0]);
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
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $users[0]);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $users[0]->getPhonenumbers());
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
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $users[0]);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $users[0]->getPhonenumbers());
        $this->assertFalse($users[0]->getPhonenumbers()->isInitialized());
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $users[0]->getAddress());
        $this->assertTrue($users[0]->getAddress()->getUser() == $users[0]);
        $this->assertEquals('germany', $users[0]->getAddress()->getCountry());
        $this->assertEquals(10827, $users[0]->getAddress()->getZipCode());
        $this->assertEquals('Berlin', $users[0]->getAddress()->getCity());
    }

    public function testFluentInterface()
    {
        $parameters = new ArrayCollection;
        $parameters->add(new Parameter(1, 'foo'));
        $parameters->add(new Parameter(2, 'bar'));

        $rsm = new ResultSetMapping;

        $q = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
        $q2 = $q->setSql('foo', $rsm)
          ->setResultSetMapping($rsm)
          ->expireResultCache(true)
          ->setHint('foo', 'bar')
          ->setParameter(1, 'foo')
          ->setParameters($parameters)
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
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $users[0]);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $users[0]->getPhonenumbers());
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
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $users[0]);
        $this->assertEquals('Roman', $users[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $users[0]->getPhonenumbers());
        $this->assertFalse($users[0]->getPhonenumbers()->isInitialized());
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $users[0]->getAddress());
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


    /**
     * @group DDC-1663
     */
    public function testBasicNativeNamedQueryWithSqlResultSetMapping()
    {
        $user           = new CmsUser;
        $user->name     = 'Fabio B. Silva';
        $user->username = 'FabioBatSilva';
        $user->status   = 'dev';

        $addr           = new CmsAddress;
        $addr->country  = 'Brazil';
        $addr->zip      = 10827;
        $addr->city     = 'São Paulo';

        $user->setAddress($addr);

        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();


        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $query      = $repository->createNativeNamedQuery('find-all');
        $result     = $query->getResult();

        $this->assertCount(1, $result);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $result[0]);
        $this->assertEquals($addr->id,  $result[0]->id);
        $this->assertEquals($addr->city,  $result[0]->city);
        $this->assertEquals($addr->country, $result[0]->country);
    }

    /**
     * @group DDC-1663
     */
    public function testBasicNativeNamedQueryWithResultClass()
    {
        $user           = new CmsUser;
        $user->name     = 'Fabio B. Silva';
        $user->username = 'FabioBatSilva';
        $user->status   = 'dev';

        $email          = new CmsEmail();
        $email->email   = 'fabio.bat.silva@gmail.com';

        $user->setEmail($email);

        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');


        $result = $repository->createNativeNamedQuery('fetchIdAndUsernameWithResultClass')
                        ->setParameter(1, 'FabioBatSilva')->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0]);
        $this->assertNull($result[0]->name);
        $this->assertNull($result[0]->email);
        $this->assertEquals($user->id, $result[0]->id);
        $this->assertEquals('FabioBatSilva', $result[0]->username);

        $this->_em->clear();


        $result = $repository->createNativeNamedQuery('fetchAllColumns')
                        ->setParameter(1, 'FabioBatSilva')->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0]);
        $this->assertEquals($user->id, $result[0]->id);
        $this->assertEquals('Fabio B. Silva', $result[0]->name);
        $this->assertEquals('FabioBatSilva', $result[0]->username);
        $this->assertEquals('dev', $result[0]->status);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsEmail', $result[0]->email);
    }


    /**
     * @group DDC-1663
     */
    public function testJoinedOneToOneNativeNamedQueryWithResultSetMapping()
    {
        $user           = new CmsUser;
        $user->name     = 'Fabio B. Silva';
        $user->username = 'FabioBatSilva';
        $user->status   = 'dev';

        $addr           = new CmsAddress;
        $addr->country  = 'Brazil';
        $addr->zip      = 10827;
        $addr->city     = 'São Paulo';


        $user->setAddress($addr);

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');


        $result = $repository->createNativeNamedQuery('fetchJoinedAddress')
                        ->setParameter(1, 'FabioBatSilva')->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0]);
        $this->assertEquals('Fabio B. Silva', $result[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $result[0]->getPhonenumbers());
        $this->assertFalse($result[0]->getPhonenumbers()->isInitialized());
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $result[0]->getAddress());
        $this->assertTrue($result[0]->getAddress()->getUser() == $result[0]);
        $this->assertEquals('Brazil', $result[0]->getAddress()->getCountry());
        $this->assertEquals(10827, $result[0]->getAddress()->getZipCode());
        $this->assertEquals('São Paulo', $result[0]->getAddress()->getCity());
    }

    /**
     * @group DDC-1663
     */
    public function testJoinedOneToManyNativeNamedQueryWithResultSetMapping()
    {
        $user               = new CmsUser;
        $user->name         = 'Fabio B. Silva';
        $user->username     = 'FabioBatSilva';
        $user->status       = 'dev';

        $phone              = new CmsPhonenumber;
        $phone->phonenumber = 424242;

        $user->addPhonenumber($phone);

        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $result = $repository->createNativeNamedQuery('fetchJoinedPhonenumber')
                        ->setParameter(1, 'FabioBatSilva')->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0]);
        $this->assertEquals('Fabio B. Silva', $result[0]->name);
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $result[0]->getPhonenumbers());
        $this->assertTrue($result[0]->getPhonenumbers()->isInitialized());
        $this->assertEquals(1, count($result[0]->getPhonenumbers()));
        $phones = $result[0]->getPhonenumbers();
        $this->assertEquals(424242, $phones[0]->phonenumber);
        $this->assertTrue($phones[0]->getUser() === $result[0]);
    }

    /**
     * @group DDC-1663
     */
    public function testMixedNativeNamedQueryNormalJoin()
    {
        $user1                  = new CmsUser;
        $user1->name            = 'Fabio B. Silva';
        $user1->username        = 'FabioBatSilva';
        $user1->status          = 'dev';

        $user2                  = new CmsUser;
        $user2->name            = 'test tester';
        $user2->username        = 'test';
        $user2->status          = 'tester';

        $phone1                 = new CmsPhonenumber;
        $phone2                 = new CmsPhonenumber;
        $phone3                 = new CmsPhonenumber;
        $phone1->phonenumber    = 11111111;
        $phone2->phonenumber    = 22222222;
        $phone3->phonenumber    = 33333333;

        $user1->addPhonenumber($phone1);
        $user1->addPhonenumber($phone2);
        $user2->addPhonenumber($phone3);

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $result = $repository->createNativeNamedQuery('fetchUserPhonenumberCount')
                        ->setParameter(1, array('test','FabioBatSilva'))->getResult();

        $this->assertEquals(2, count($result));
        $this->assertTrue(is_array($result[0]));
        $this->assertTrue(is_array($result[1]));

        // first user => 2 phonenumbers
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0][0]);
        $this->assertEquals('Fabio B. Silva', $result[0][0]->name);
        $this->assertEquals(2, $result[0]['numphones']);

        // second user => 1 phonenumbers
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[1][0]);
        $this->assertEquals('test tester', $result[1][0]->name);
        $this->assertEquals(1, $result[1]['numphones']);
    }

    /**
     * @group DDC-1663
     */
    public function testNativeNamedQueryInheritance()
    {
        $person = new CompanyPerson;
        $person->setName('Fabio B. Silva');

        $employee = new CompanyEmployee;
        $employee->setName('Fabio Silva');
        $employee->setSalary(100000);
        $employee->setDepartment('IT');

        $this->_em->persist($person);
        $this->_em->persist($employee);

        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Company\CompanyPerson');

        $result = $repository->createNativeNamedQuery('fetchAllWithSqlResultSetMapping')
                        ->getResult();

        $this->assertEquals(2, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyPerson', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $result[1]);
        $this->assertTrue(is_numeric($result[0]->getId()));
        $this->assertTrue(is_numeric($result[1]->getId()));
        $this->assertEquals('Fabio B. Silva', $result[0]->getName());
        $this->assertEquals('Fabio Silva', $result[1]->getName());


        $this->_em->clear();


        $result = $repository->createNativeNamedQuery('fetchAllWithResultClass')
                        ->getResult();

        $this->assertEquals(2, count($result));
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyPerson', $result[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\Company\CompanyEmployee', $result[1]);
        $this->assertTrue(is_numeric($result[0]->getId()));
        $this->assertTrue(is_numeric($result[1]->getId()));
        $this->assertEquals('Fabio B. Silva', $result[0]->getName());
        $this->assertEquals('Fabio Silva', $result[1]->getName());
    }

    /**
     * @group DDC-1663
     * DQL : SELECT u, a, COUNT(p) AS numphones FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.address a JOIN u.phonenumbers p
     */
    public function testMultipleEntityResults()
    {

        $user               = new CmsUser;
        $user->name         = 'Fabio B. Silva';
        $user->username     = 'FabioBatSilva';
        $user->status       = 'dev';

        $addr               = new CmsAddress;
        $addr->country      = 'Brazil';
        $addr->zip          = 10827;
        $addr->city         = 'São Paulo';

        $phone              = new CmsPhonenumber;
        $phone->phonenumber = 424242;


        $user->setAddress($addr);
        $user->addPhonenumber($phone);


        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();

        $this->_em->clear();


        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $query      = $repository->createNativeNamedQuery('fetchMultipleJoinsEntityResults');
        $result     = $query->getResult();


        $this->assertEquals(1, count($result));
        $this->assertTrue(is_array($result[0]));

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $result[0][0]);
        $this->assertEquals('Fabio B. Silva', $result[0][0]->name);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $result[0][0]->getAddress());
        $this->assertTrue($result[0][0]->getAddress()->getUser() == $result[0][0]);
        $this->assertEquals('Brazil', $result[0][0]->getAddress()->getCountry());
        $this->assertEquals(10827, $result[0][0]->getAddress()->getZipCode());

        $this->assertEquals(1, $result[0]['numphones']);

    }

    /**
     * @group DDC-1663
     */
    public function testNamedNativeQueryInheritance()
    {
        $contractMetadata   = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyContract');
        $flexMetadata       = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyFlexContract');

        $contractQueries    = $contractMetadata->getNamedNativeQueries();
        $flexQueries        = $flexMetadata->getNamedNativeQueries();

        $contractMappings   = $contractMetadata->getSqlResultSetMappings();
        $flexMappings       = $flexMetadata->getSqlResultSetMappings();


        // contract queries
        $this->assertEquals('all-contracts', $contractQueries['all-contracts']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyContract', $contractQueries['all-contracts']['resultClass']);

        $this->assertEquals('all', $contractQueries['all']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyContract', $contractQueries['all']['resultClass']);


        // flex contract queries
        $this->assertEquals('all-contracts', $flexQueries['all-contracts']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexQueries['all-contracts']['resultClass']);

        $this->assertEquals('all-flex', $flexQueries['all-flex']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexQueries['all-flex']['resultClass']);

        $this->assertEquals('all', $flexQueries['all']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexQueries['all']['resultClass']);


        // contract result mapping
        $this->assertEquals('mapping-all-contracts', $contractMappings['mapping-all-contracts']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyContract', $contractMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        $this->assertEquals('mapping-all', $contractMappings['mapping-all']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyContract', $contractMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        // flex contract result mapping
        $this->assertEquals('mapping-all-contracts', $flexMappings['mapping-all-contracts']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        $this->assertEquals('mapping-all', $flexMappings['mapping-all']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexMappings['mapping-all']['entities'][0]['entityClass']);

        $this->assertEquals('mapping-all-flex', $flexMappings['mapping-all-flex']['name']);
        $this->assertEquals('Doctrine\Tests\Models\Company\CompanyFlexContract', $flexMappings['mapping-all-flex']['entities'][0]['entityClass']);

    }

}