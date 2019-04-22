<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Internal\Hydration\HydrationException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Company\CompanyContract;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\DDC3899\DDC3899FixContract;
use Doctrine\Tests\Models\DDC3899\DDC3899User;
use Doctrine\Tests\OrmFunctionalTestCase;
use InvalidArgumentException;
use ReflectionClass;

/**
 * NativeQueryTest
 */
class NativeQueryTest extends OrmFunctionalTestCase
{
    private $platform;

    protected function setUp() : void
    {
        $this->useModelSet('cms');
        $this->useModelSet('company');

        parent::setUp();

        $this->platform = $this->em->getConnection()->getDatabasePlatform();
    }

    public function testBasicNativeQuery() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');

        $query = $this->em->createNativeQuery('SELECT id, name FROM cms_users WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
    }

    public function testBasicNativeQueryWithMetaResult() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $addr = new CmsAddress();

        $addr->country = 'germany';
        $addr->zip     = 10827;
        $addr->city    = 'Berlin';

        $user->setAddress($addr);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsAddress::class, 'a');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');
        $rsm->addMetaResult('a', $this->platform->getSQLResultCasing('user_id'), 'user_id', false, DBALType::getType('integer'));

        $query = $this->em->createNativeQuery('SELECT a.id, a.country, a.zip, a.city, a.user_id FROM cms_addresses a WHERE a.id = ?', $rsm);

        $query->setParameter(1, $addr->id);

        $addresses = $query->getResult();

        self::assertCount(1, $addresses);
        self::assertInstanceOf(CmsAddress::class, $addresses[0]);
        self::assertEquals($addr->country, $addresses[0]->country);
        self::assertEquals($addr->zip, $addresses[0]->zip);
        self::assertEquals($addr->city, $addresses[0]->city);
        self::assertEquals($addr->street, $addresses[0]->street);
        self::assertInstanceOf(CmsUser::class, $addresses[0]->user);
    }

    public function testJoinedOneToManyNativeQuery() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $phone = new CmsPhonenumber();

        $phone->phonenumber = 424242;

        $user->addPhonenumber($phone);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $rsm->addFieldResult('p', $this->platform->getSQLResultCasing('phonenumber'), 'phonenumber');

        $query = $this->em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertTrue($users[0]->getPhonenumbers()->isInitialized());
        self::assertCount(1, $users[0]->getPhonenumbers());

        $phones = $users[0]->getPhonenumbers();

        self::assertEquals(424242, $phones[0]->phonenumber);
        self::assertSame($phones[0]->getUser(), $users[0]);
    }

    public function testJoinedOneToOneNativeQuery() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $addr = new CmsAddress();

        $addr->country = 'germany';
        $addr->zip     = 10827;
        $addr->city    = 'Berlin';

        $user->setAddress($addr);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMapping();

        $rsm->addEntityResult(CmsUser::class, 'u');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('name'), 'name');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('status'), 'status');
        $rsm->addJoinedEntityResult(CmsAddress::class, 'a', 'u', 'address');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('a_id'), 'id');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('country'), 'country');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('zip'), 'zip');
        $rsm->addFieldResult('a', $this->platform->getSQLResultCasing('city'), 'city');

        $query = $this->em->createNativeQuery('SELECT u.id, u.name, u.status, a.id AS a_id, a.country, a.zip, a.city FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertFalse($users[0]->getPhonenumbers()->isInitialized());
        self::assertInstanceOf(CmsAddress::class, $users[0]->getAddress());
        self::assertEquals($users[0]->getAddress()->getUser(), $users[0]);
        self::assertEquals('germany', $users[0]->getAddress()->getCountry());
        self::assertEquals(10827, $users[0]->getAddress()->getZipCode());
        self::assertEquals('Berlin', $users[0]->getAddress()->getCity());
    }

    public function testFluentInterface() : void
    {
        $parameters = new ArrayCollection();

        $parameters->add(new Parameter(1, 'foo'));
        $parameters->add(new Parameter(2, 'bar'));

        $rsm = new ResultSetMapping();

        $q  = $this->em->createNativeQuery('SELECT id, name, status, phonenumber FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?', $rsm);
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

    public function testJoinedOneToManyNativeQueryWithRSMBuilder() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $phone = new CmsPhonenumber();

        $phone->phonenumber = 424242;

        $user->addPhonenumber($phone);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $query = $this->em->createNativeQuery('SELECT u.*, p.* FROM cms_users u LEFT JOIN cms_phonenumbers p ON u.id = p.user_id WHERE username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertTrue($users[0]->getPhonenumbers()->isInitialized());
        self::assertCount(1, $users[0]->getPhonenumbers());

        $phones = $users[0]->getPhonenumbers();

        self::assertEquals(424242, $phones[0]->phonenumber);
        self::assertSame($phones[0]->getUser(), $users[0]);

        $this->em->clear();

        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsPhonenumber::class, 'p');

        $query = $this->em->createNativeQuery('SELECT p.* FROM cms_phonenumbers p WHERE p.phonenumber = ?', $rsm);

        $query->setParameter(1, $phone->phonenumber);

        $phone = $query->getSingleResult();

        self::assertNotNull($phone->getUser());
        self::assertEquals($user->name, $phone->getUser()->getName());
    }

    public function testJoinedOneToOneNativeQueryWithRSMBuilder() : void
    {
        $user = new CmsUser();

        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';

        $addr = new CmsAddress();

        $addr->country = 'germany';
        $addr->zip     = 10827;
        $addr->city    = 'Berlin';

        $user->setAddress($addr);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'u', 'address', ['id' => 'a_id']);

        $query = $this->em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $users = $query->getResult();

        self::assertCount(1, $users);
        self::assertInstanceOf(CmsUser::class, $users[0]);
        self::assertEquals('Roman', $users[0]->name);
        self::assertInstanceOf(PersistentCollection::class, $users[0]->getPhonenumbers());
        self::assertFalse($users[0]->getPhonenumbers()->isInitialized());
        self::assertInstanceOf(CmsAddress::class, $users[0]->getAddress());
        self::assertEquals($users[0]->getAddress()->getUser(), $users[0]);
        self::assertEquals('germany', $users[0]->getAddress()->getCountry());
        self::assertEquals(10827, $users[0]->getAddress()->getZipCode());
        self::assertEquals('Berlin', $users[0]->getAddress()->getCity());

        $this->em->clear();

        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsAddress::class, 'a');

        $query = $this->em->createNativeQuery('SELECT a.* FROM cms_addresses a WHERE a.id = ?', $rsm);

        $query->setParameter(1, $addr->getId());

        $address = $query->getSingleResult();

        self::assertNotNull($address->getUser());
        self::assertEquals($user->name, $address->getUser()->getName());
    }

    /**
     * @group rsm-sti
     */
    public function testConcreteClassInSingleTableInheritanceSchemaWithRSMBuilderIsFine() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CompanyFixContract::class, 'c');

        self::assertSame(CompanyFixContract::class, $rsm->getClassName('c'));
    }

    /**
     * @group rsm-sti
     */
    public function testAbstractClassInSingleTableInheritanceSchemaWithRSMBuilderThrowsException() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ResultSetMapping builder does not currently support your inheritance scheme.');

        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CompanyContract::class, 'c');
    }

    public function testRSMBuilderThrowsExceptionOnColumnConflict() : void
    {
        $this->expectException('InvalidArgumentException');
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'u', 'address');
    }

    /**
     * @group PR-39
     */
    public function testUnknownParentAliasThrowsException() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');
        $rsm->addJoinedEntityFromClassMetadata(CmsAddress::class, 'a', 'un', 'address', ['id' => 'a_id']);

        $query = $this->em->createNativeQuery('SELECT u.*, a.*, a.id AS a_id FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?', $rsm);

        $query->setParameter(1, 'romanb');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage("The parent object of entity result with alias 'a' was not found. The parent alias is 'un'.");

        $query->getResult();
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseNoRenameSingleEntity() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id, u.status AS status, u.username AS username, u.name AS name, u.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseCustomRenames() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(
            CmsUser::class,
            'u',
            [
                'id' => 'id1',
                'username' => 'username2',
            ]
        );

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id1, u.status AS status, u.username AS username2, u.name AS name, u.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseRenameTableAlias() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause(['u' => 'u1']);

        self::assertSQLEquals('u1.id AS id, u1.status AS status, u1.username AS username, u1.name AS name, u1.email_id AS email_id', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseIncrement() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        $selectClause = $rsm->generateSelectClause();

        self::assertSQLEquals('u.id AS id0, u.status AS status1, u.username AS username2, u.name AS name3, u.email_id AS email_id4', $selectClause);
    }

    /**
     * @group DDC-2055
     */
    public function testGenerateSelectClauseToString() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addRootEntityFromClassMetadata(CmsUser::class, 'u');

        self::assertSQLEquals('u.id AS id0, u.status AS status1, u.username AS username2, u.name AS name3, u.email_id AS email_id4', (string) $rsm);
    }

    /**
     * @group DDC-3899
     */
    public function testGenerateSelectClauseWithDiscriminatorColumn() : void
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);

        $rsm->addEntityResult(DDC3899User::class, 'u');
        $rsm->addJoinedEntityResult(DDC3899FixContract::class, 'c', 'u', 'contracts');
        $rsm->addFieldResult('u', $this->platform->getSQLResultCasing('id'), 'id');
        $rsm->setDiscriminatorColumn('c', $this->platform->getSQLResultCasing('discr'));

        $selectClause = $rsm->generateSelectClause(['u' => 'u1', 'c' => 'c1']);

        self::assertSQLEquals('u1.id as id, c1.discr as discr', $selectClause);
    }

    protected function getResultSetMapping(AbstractQuery $query) : ResultSetMapping
    {
        $reflClass  = new ReflectionClass($query);
        $reflMethod = $reflClass->getMethod('getResultSetMapping');

        $reflMethod->setAccessible(true);

        return $reflMethod->invoke($query);
    }
}
