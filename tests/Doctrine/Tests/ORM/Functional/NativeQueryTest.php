<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Internal\Hydration\HydrationException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\Models\DDC3899\DDC3899FixContract;
use Doctrine\Tests\Models\DDC3899\DDC3899User;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NativeQueryTest extends OrmFunctionalTestCase
{
    private $platform = null;

    protected function setUp()
    {
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

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');

        $query = $this->_em->createNativeQuery('SELECT id, name FROM cms_users WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
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

        $rsm->addEntityResult(CmsAddress::class, 'a');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');
        $rsm->addMetaResult('a', $this->platform->getSQLResultCasing('user_id'), 'user_id', false, Type::getType('integer'));

        $query = $this->_em->createNativeQuery('SELECT a.id, a.country, a.zip, a.city, a.user_id FROM cms_addresses a WHERE a.id = ?', $rsm);

        $query->setParameter(1, $addr->id);

        $addresses = $query->getResult();

        self::assertEquals(1, count($addresses));
        self::assertTrue($addresses[0] instanceof CmsAddress);
        self::assertEquals($addr->country, $addresses[0]->country);
        self::assertEquals($addr->zip, $addresses[0]->zip);
        self::assertEquals($addr->city, $addresses[0]->city);
        self::assertEquals($addr->street, $addresses[0]->street);
        self::assertTrue($addresses[0]->user instanceof CmsUser);
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

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', $this->platform->getSQLResultCasing('phonenumber'), 'phonenumber');

        $query = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertTrue($users[0]->getPhonenumbers()->isInitialized());
        self::assertEquals(1, count($users[0]->getPhonenumbers()));

        $phones = $users[0]->getPhonenumbers();

        self::assertEquals(424242, $phones[0]->phonenumber);
        self::assertTrue($phones[0]->getUser() === $users[0]);
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

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult(CmsAddress::class, 'a', 'u', 'address');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('a_id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');

        $query = $this->_em->createNativeQuery('SELECT u.id, u.name, u.status, a.id AS a_id, a.country, a.zip, a.city FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertFalse($users[0]->getPhonenumbers()->isInitialized());
        self::assertInstanceOf(CmsAddress::class, $users[0]->getAddress());
        self::assertTrue($users[0]->getAddress()->getUser() == $users[0]);
        self::assertEquals('germany', $users[0]->getAddress()->getCountry());
        self::assertEquals(10827, $users[0]->getAddress()->getZipCode());
        self::assertEquals('Berlin', $users[0]->getAddress()->getCity());
    }

    public function testFluentInterface()
    {
        $parameters = new ArrayCollection;

        $parameters->add(new Parameter(1, 'foo'));
        $parameters->add(new Parameter(2, 'bar'));

        $rsm = new ResultSetMapping;

        $q = $this->_em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
        $q2 = $q->setSQL('foo')
          ->setResultSetMapping($rsm)
          ->expireResultCache(true)
          ->setHint('foo', 'bar')
          ->setParameter(1, 'foo')
          ->setParameters($parameters)
          ->setResultCacheDriver(null)
          ->setResultCacheLifetime(3500);

        self::assertSame($q, $q2);
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

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $query = $this->_em->createNativeQuery('SELECT u.*, p.* FROM cms_users u LEFT JOIN cms_phonenumbers p ON u.id = p.user_id WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertTrue($users[0]->getPhonenumbers()->isInitialized());
        self::assertEquals(1, count($users[0]->getPhonenumbers()));

        $phones = $users[0]->getPhonenumbers();

        self::assertEquals(424242, $phones[0]->phonenumber);
        self::assertTrue($phones[0]->getUser() === $users[0]);

        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsPhonenumber::class, 'p');

        $query = $this->_em->createNativeQuery('SELECT p.* FROM cms_phonenumbers p WHERE p.phonenumber = ?', $rsm);

        $query->setParameter(1, $phone->phonenumber);

        $phone = $query->getSingleResult();

        self::assertNotNull($phone->getUser());
        self::assertEquals($user->name, $phone->getUser()->getName());
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

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'u', 'address', ['id' => 'a_id']);

        $query = $this->_em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertEquals(1, count($users));
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertFalse($users[0]->getPhonenumbers()->isInitialized());
        self::assertInstanceOf(CmsAddress::class, $users[0]->getAddress());
        self::assertTrue($users[0]->getAddress()->getUser() == $users[0]);
        self::assertEquals('germany', $users[0]->getAddress()->getCountry());
        self::assertEquals(10827, $users[0]->getAddress()->getZipCode());
        self::assertEquals('Berlin', $users[0]->getAddress()->getCity());

        $this->_em->clear();

        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsAddress::class, 'a');

        $query = $this->_em->createNativeQuery('SELECT a.* FROM cms_addresses a WHERE a.id = ?', $rsm);

        $query->setParameter(1, $addr->getId());

        $address = $query->getSingleResult();

        self::assertNotNull($address->getUser());
        self::assertEquals($user->name, $address->getUser()->getName());
    }

    /**
     * @group rsm-sti
     */
    public function testConcreteClassInSingleTableInheritanceSchemaWithRSMBuilderIsFine()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CompanyFixContract::class, 'c');

        self::assertSame(CompanyFixContract::class, $rsm->getClassName('c'));
    }

    /**
     * @group rsm-sti
     */
    public function testAbstractClassInSingleTableInheritanceSchemaWithRSMBuilderThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ResultSetMapping builder does not currently support your inheritance scheme.');

        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CompanyContract::class, 'c');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRSMBuilderThrowsExceptionOnColumnConflict()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'u', 'address');
    }

    /**
     * @group PR-39
     */
    public function testUnknownParentAliasThrowsException()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'un', 'address', ['id' => 'a_id']);

        $query = $this->_em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage("The parent object of entity result with alias 'a' was not found. The parent alias is 'un'.");

        $query->getResult();
    }


    /**
     * @group DDC-1663
     */
    public function testBasicNativeNamedQueryWithSqlResultSetMapping()
    {
        $user = new CmsUser;

        $user->name     = 'Fabio B. Silva';
        $user->username = 'FabioBatSilva';
        $user->status   = 'dev';

        $addr = new CmsAddress;

        $addr->country  = 'Brazil';
        $addr->zip      = 10827;
        $addr->city     = 'São Paulo';

        $user->setAddress($addr);

        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(CmsAddress::class);
        $query      = $repository->createNativeNamedQuery('find-all');
        $result     = $query->getResult();

        self::assertCount(1, $result);
        self::assertInstanceOf(CmsAddress::class, $result[0]);
        self::assertEquals($addr->id,  $result[0]->id);
        self::assertEquals($addr->city,  $result[0]->city);
        self::assertEquals($addr->country, $result[0]->country);
    }

    /**
     * @group DDC-1663
     */
    public function testBasicNativeNamedQueryWithResultClass()
    {
        $user = new CmsUser;

        $user->name     = 'Fabio B. Silva';
        $user->username = 'FabioBatSilva';
        $user->status   = 'dev';

        $email = new CmsEmail();

        $email->email   = 'fabio.bat.silva@gmail.com';

        $user->setEmail($email);

        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(CmsUser::class);
        $result     = $repository
            ->createNativeNamedQuery('fetchIdAndUsernameWithResultClass')
            ->setParameter(1, 'FabioBatSilva')
            ->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertNull($result[0]->name);
        self::assertNull($result[0]->email);
        self::assertEquals($user->id, $result[0]->id);
        self::assertEquals('FabioBatSilva', $result[0]->username);

        $this->_em->clear();

        $result = $repository
            ->createNativeNamedQuery('fetchAllColumns')
            ->setParameter(1, 'FabioBatSilva')
            ->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertEquals($user->id, $result[0]->id);
        self::assertEquals('Fabio B. Silva', $result[0]->name);
        self::assertEquals('FabioBatSilva', $result[0]->username);
        self::assertEquals('dev', $result[0]->status);
        self::assertInstanceOf(CmsEmail::class, $result[0]->email);
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

        $result = $this->_em->getRepository(CmsUser::class)
                            ->createNativeNamedQuery('fetchJoinedAddress')
                            ->setParameter(1, 'FabioBatSilva')
                            ->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertEquals('Fabio B. Silva', $result[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $result[0]->getPhonenumbers());
        self::assertFalse($result[0]->getPhonenumbers()->isInitialized());
        self::assertInstanceOf(CmsAddress::class, $result[0]->getAddress());
        self::assertTrue($result[0]->getAddress()->getUser() == $result[0]);
        self::assertEquals('Brazil', $result[0]->getAddress()->getCountry());
        self::assertEquals(10827, $result[0]->getAddress()->getZipCode());
        self::assertEquals('São Paulo', $result[0]->getAddress()->getCity());
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

        $repository = $this->_em->getRepository(CmsUser::class);

        $result = $repository->createNativeNamedQuery('fetchJoinedPhonenumber')
                        ->setParameter(1, 'FabioBatSilva')->getResult();

        self::assertEquals(1, count($result));
        self::assertInstanceOf(CmsUser::class, $result[0]);
        self::assertEquals('Fabio B. Silva', $result[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $result[0]->getPhonenumbers());
        self::assertTrue($result[0]->getPhonenumbers()->isInitialized());
        self::assertEquals(1, count($result[0]->getPhonenumbers()));
        $phones = $result[0]->getPhonenumbers();
        self::assertEquals(424242, $phones[0]->phonenumber);
        self::assertTrue($phones[0]->getUser() === $result[0]);
    }

    /**
     * @group DDC-1663
     */
    public function testMixedNativeNamedQueryNormalJoin()
    {
        $user1 = new CmsUser;

        $user1->name            = 'Fabio B. Silva';
        $user1->username        = 'FabioBatSilva';
        $user1->status          = 'dev';

        $user2 = new CmsUser;

        $user2->name            = 'test tester';
        $user2->username        = 'test';
        $user2->status          = 'tester';

        $phone1 = new CmsPhonenumber;
        $phone2 = new CmsPhonenumber;
        $phone3 = new CmsPhonenumber;

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

        $repository = $this->_em->getRepository(CmsUser::class);
        $result     = $repository
            ->createNativeNamedQuery('fetchUserPhonenumberCount')
            ->setParameter(1, ['test','FabioBatSilva'])->getResult();

        self::assertEquals(2, count($result));
        self::assertTrue(is_array($result[0]));
        self::assertTrue(is_array($result[1]));

        // first user => 2 phonenumbers
        self::assertInstanceOf(CmsUser::class, $result[0][0]);
        self::assertEquals('Fabio B. Silva', $result[0][0]->name);
        self::assertEquals(2, $result[0]['numphones']);

        // second user => 1 phonenumbers
        self::assertInstanceOf(CmsUser::class, $result[1][0]);
        self::assertEquals('test tester', $result[1][0]->name);
        self::assertEquals(1, $result[1]['numphones']);
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

        $repository = $this->_em->getRepository(CompanyPerson::class);
        $result     = $repository
            ->createNativeNamedQuery('fetchAllWithSqlResultSetMapping')
            ->getResult();

        self::assertEquals(2, count($result));
        self::assertInstanceOf(CompanyPerson::class, $result[0]);
        self::assertInstanceOf(CompanyEmployee::class, $result[1]);
        self::assertTrue(is_numeric($result[0]->getId()));
        self::assertTrue(is_numeric($result[1]->getId()));
        self::assertEquals('Fabio B. Silva', $result[0]->getName());
        self::assertEquals('Fabio Silva', $result[1]->getName());

        $this->_em->clear();

        $result = $repository
            ->createNativeNamedQuery('fetchAllWithResultClass')
            ->getResult();

        self::assertEquals(2, count($result));
        self::assertInstanceOf(CompanyPerson::class, $result[0]);
        self::assertInstanceOf(CompanyEmployee::class, $result[1]);
        self::assertTrue(is_numeric($result[0]->getId()));
        self::assertTrue(is_numeric($result[1]->getId()));
        self::assertEquals('Fabio B. Silva', $result[0]->getName());
        self::assertEquals('Fabio Silva', $result[1]->getName());
    }

    /**
     * @group DDC-1663
     * DQL : SELECT u, a, COUNT(p) AS numphones FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.address a JOIN u.phonenumbers p
     */
    public function testMultipleEntityResults()
    {
        $user = new CmsUser;

        $user->name         = 'Fabio B. Silva';
        $user->username     = 'FabioBatSilva';
        $user->status       = 'dev';

        $addr = new CmsAddress;

        $addr->country      = 'Brazil';
        $addr->zip          = 10827;
        $addr->city         = 'São Paulo';

        $phone = new CmsPhonenumber;

        $phone->phonenumber = 424242;

        $user->setAddress($addr);
        $user->addPhonenumber($phone);

        $this->_em->clear();
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();


        $repository = $this->_em->getRepository(CmsUser::class);
        $query      = $repository->createNativeNamedQuery('fetchMultipleJoinsEntityResults');
        $result     = $query->getResult();

        self::assertEquals(1, count($result));
        self::assertTrue(is_array($result[0]));
        self::assertInstanceOf(CmsUser::class, $result[0][0]);
        self::assertEquals('Fabio B. Silva', $result[0][0]->name);
        self::assertInstanceOf(CmsAddress::class, $result[0][0]->getAddress());
        self::assertTrue($result[0][0]->getAddress()->getUser() == $result[0][0]);
        self::assertEquals('Brazil', $result[0][0]->getAddress()->getCountry());
        self::assertEquals(10827, $result[0][0]->getAddress()->getZipCode());

        self::assertEquals(1, $result[0]['numphones']);
    }

    /**
     * @group DDC-1663
     */
    public function testNamedNativeQueryInheritance()
    {
        $contractMetadata   = $this->_em->getClassMetadata(CompanyContract::class);
        $flexMetadata       = $this->_em->getClassMetadata(CompanyFlexContract::class);

        $contractQueries    = $contractMetadata->getNamedNativeQueries();
        $flexQueries        = $flexMetadata->getNamedNativeQueries();

        $contractMappings   = $contractMetadata->getSqlResultSetMappings();
        $flexMappings       = $flexMetadata->getSqlResultSetMappings();

        // contract queries
        self::assertEquals('all-contracts', $contractQueries['all-contracts']['name']);
        self::assertEquals(CompanyContract::class, $contractQueries['all-contracts']['resultClass']);

        self::assertEquals('all', $contractQueries['all']['name']);
        self::assertEquals(CompanyContract::class, $contractQueries['all']['resultClass']);


        // flex contract queries
        self::assertEquals('all-contracts', $flexQueries['all-contracts']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexQueries['all-contracts']['resultClass']);

        self::assertEquals('all-flex', $flexQueries['all-flex']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexQueries['all-flex']['resultClass']);

        self::assertEquals('all', $flexQueries['all']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexQueries['all']['resultClass']);


        // contract result mapping
        self::assertEquals('mapping-all-contracts', $contractMappings['mapping-all-contracts']['name']);
        self::assertEquals(CompanyContract::class, $contractMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        self::assertEquals('mapping-all', $contractMappings['mapping-all']['name']);
        self::assertEquals(CompanyContract::class, $contractMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        // flex contract result mapping
        self::assertEquals('mapping-all-contracts', $flexMappings['mapping-all-contracts']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexMappings['mapping-all-contracts']['entities'][0]['entityClass']);

        self::assertEquals('mapping-all', $flexMappings['mapping-all']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexMappings['mapping-all']['entities'][0]['entityClass']);

        self::assertEquals('mapping-all-flex', $flexMappings['mapping-all-flex']['name']);
        self::assertEquals(CompanyFlexContract::class, $flexMappings['mapping-all-flex']['entities'][0]['entityClass']);

    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseNoRenameSingleEntity()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id, u.status AS status, u.username AS username, u.name AS name, u.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseCustomRenames()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(
            CmsUser::class,
            'u',
            [
                'id' => 'id1',
                'username' => 'username2'
            ]
        );

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id1, u.status AS status, u.username AS username2, u.name AS name, u.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseRenameTableAlias()
    {
        $rsm = new ResultSetMappingBuilder($this->_em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause(['u' => 'u1']);

        self::assertSQLEquals('u1.id AS id, u1.status AS status, u1.username AS username, u1.name AS name, u1.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseIncrement()
    {
        $rsm = new ResultSetMappingBuilder($this->_em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id0, u.status AS status1, u.username AS username2, u.name AS name3, u.email_id AS email_id4', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseToString()
    {
        $rsm = new ResultSetMappingBuilder($this->_em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        self::assertSQLEquals('u.id AS id0, u.status AS status1, u.username AS username2, u.name AS name3, u.email_id AS email_id4', (string)$rsm);
    }

    /**
     * @group DDC-3899
     */
    public function testGenerateSelectClauseWithDiscriminatorColumn()
    {
        $rsm = new ResultSetMappingBuilder($this->_em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addEntityResult(DDC3899User::class, 'u');
        $rsm->addJoinedEntityResult(DDC3899FixContract::class, 'c', 'u', 'contracts');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->setDiscriminatorColumn('c', $this->platform->getSQLResultCasing('discr'));

        $selectClause = $rsm->generateSelectClause(['u' => 'u1', 'c' => 'c1']);

        self::assertSQLEquals('u1.id as id, c1.discr as discr', $selectClause);
    }
}
