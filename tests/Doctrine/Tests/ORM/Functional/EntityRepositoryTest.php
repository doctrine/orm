<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author robo
 */
class EntityRepositoryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_em) {
            $this->_em->getConfiguration()->setEntityNamespaces(array());
        }
        parent::tearDown();
    }

    public function loadFixture()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'freak';
        $this->_em->persist($user);

        $user2 = new CmsUser;
        $user2->name = 'Guilherme';
        $user2->username = 'gblanco';
        $user2->status = 'dev';
        $this->_em->persist($user2);

        $user3 = new CmsUser;
        $user3->name = 'Benjamin';
        $user3->username = 'beberlei';
        $user3->status = null;
        $this->_em->persist($user3);

        $user4 = new CmsUser;
        $user4->name = 'Alexander';
        $user4->username = 'asm89';
        $user4->status = 'dev';
        $this->_em->persist($user4);

        $this->_em->flush();

        $user1Id = $user->getId();

        unset($user);
        unset($user2);
        unset($user3);
        unset($user4);

        $this->_em->clear();

        return $user1Id;
    }

    public function loadAssociatedFixture()
    {
        $address = new CmsAddress();
        $address->city = "Berlin";
        $address->country = "Germany";
        $address->street = "Foostreet";
        $address->zip = "12345";

        $user = new CmsUser();
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'freak';
        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->persist($address);
        $this->_em->flush();
        $this->_em->clear();

        return array($user->id, $address->id);
    }

    public function loadFixtureUserEmail()
    {
        $user1 = new CmsUser();
        $user2 = new CmsUser();
        $user3 = new CmsUser();

        $email1 = new CmsEmail();
        $email2 = new CmsEmail();
        $email3 = new CmsEmail();

        $user1->name     = 'Test 1';
        $user1->username = 'test1';
        $user1->status   = 'active';

        $user2->name     = 'Test 2';
        $user2->username = 'test2';
        $user2->status   = 'active';

        $user3->name     = 'Test 3';
        $user3->username = 'test3';
        $user3->status   = 'active';

        $email1->email   = 'test1@test.com';
        $email2->email   = 'test2@test.com';
        $email3->email   = 'test3@test.com';

        $user1->setEmail($email1);
        $user2->setEmail($email2);
        $user3->setEmail($email3);

        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->persist($user3);

        $this->_em->persist($email1);
        $this->_em->persist($email2);
        $this->_em->persist($email3);

        $this->_em->flush();
        $this->_em->clear();

        return array($user1, $user2, $user3);
    }

    public function buildUser($name, $username, $status, $address)
    {
        $user = new CmsUser();
        $user->name     = $name;
        $user->username = $username;
        $user->status   = $status;
        $user->setAddress($address);

        $this->_em->persist($user);
        $this->_em->flush();

        return $user;
    }

    public function buildAddress($country, $city, $street, $zip)
    {
        $address = new CmsAddress();
        $address->country = $country;
        $address->city    = $city;
        $address->street  = $street;
        $address->zip     = $zip;

        $this->_em->persist($address);
        $this->_em->flush();

        return $address;
    }

    public function testBasicFind()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $user = $repos->find($user1Id);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser',$user);
        $this->assertEquals('Roman', $user->name);
        $this->assertEquals('freak', $user->status);
    }

    public function testFindByField()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findBy(array('status' => 'dev'));
        $this->assertEquals(2, count($users));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser',$users[0]);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);
    }

    public function testFindByAssociationWithIntegerAsParameter()
    {
        $address1 = $this->buildAddress('Germany', 'Berlim', 'Foo st.', '123456');
        $user1    = $this->buildUser('Benjamin', 'beberlei', 'dev', $address1);

        $address2 = $this->buildAddress('Brazil', 'São Paulo', 'Bar st.', '654321');
        $user2    = $this->buildUser('Guilherme', 'guilhermeblanco', 'freak', $address2);

        $address3 = $this->buildAddress('USA', 'Nashville', 'Woo st.', '321654');
        $user3    = $this->buildUser('Jonathan', 'jwage', 'dev', $address3);

        unset($address1);
        unset($address2);
        unset($address3);

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses  = $repository->findBy(array('user' => array($user1->getId(), $user2->getId())));

        $this->assertEquals(2, count($addresses));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress',$addresses[0]);
    }

    public function testFindByAssociationWithObjectAsParameter()
    {
        $address1 = $this->buildAddress('Germany', 'Berlim', 'Foo st.', '123456');
        $user1    = $this->buildUser('Benjamin', 'beberlei', 'dev', $address1);

        $address2 = $this->buildAddress('Brazil', 'São Paulo', 'Bar st.', '654321');
        $user2    = $this->buildUser('Guilherme', 'guilhermeblanco', 'freak', $address2);

        $address3 = $this->buildAddress('USA', 'Nashville', 'Woo st.', '321654');
        $user3    = $this->buildUser('Jonathan', 'jwage', 'dev', $address3);

        unset($address1);
        unset($address2);
        unset($address3);

        $this->_em->clear();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses  = $repository->findBy(array('user' => array($user1, $user2)));

        $this->assertEquals(2, count($addresses));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress',$addresses[0]);
    }

    public function testFindFieldByMagicCall()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findByStatus('dev');
        $this->assertEquals(2, count($users));
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser',$users[0]);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);
    }

    public function testFindAll()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findAll();
        $this->assertEquals(4, count($users));
    }

    public function testFindByAlias()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $this->_em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        $repos = $this->_em->getRepository('CMS:CmsUser');

        $users = $repos->findAll();
        $this->assertEquals(4, count($users));
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testExceptionIsThrownWhenCallingFindByWithoutParameter() {
        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->findByStatus();
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testExceptionIsThrownWhenUsingInvalidFieldName() {
        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->findByThisFieldDoesNotExist('testvalue');
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticReadLockWithoutTransaction_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\TransactionRequiredException');

        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->find(1, \Doctrine\DBAL\LockMode::PESSIMISTIC_READ);
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testPessimisticWriteLockWithoutTransaction_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\TransactionRequiredException');

        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->find(1, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testOptimisticLockUnversionedEntity_ThrowsException()
    {
        $this->setExpectedException('Doctrine\ORM\OptimisticLockException');

        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
                  ->find(1, \Doctrine\DBAL\LockMode::OPTIMISTIC);
    }

    /**
     * @group locking
     * @group DDC-178
     */
    public function testIdentityMappedOptimisticLockUnversionedEntity_ThrowsException()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'freak';
        $this->_em->persist($user);
        $this->_em->flush();

        $userId = $user->id;

        $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $userId);

        $this->setExpectedException('Doctrine\ORM\OptimisticLockException');
        $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $userId, \Doctrine\DBAL\LockMode::OPTIMISTIC);
    }

    /**
     * @group DDC-819
     */
    public function testFindMagicCallByNullValue()
    {
        $this->loadFixture();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findByStatus(null);
        $this->assertEquals(1, count($users));
    }

    /**
     * @group DDC-819
     */
    public function testInvalidMagicCall()
    {
        $this->setExpectedException('BadMethodCallException');

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $repos->foo();
    }

    /**
     * @group DDC-817
     */
    public function testFindByAssociationKey_ExceptionOnInverseSide()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $this->setExpectedException('Doctrine\ORM\ORMException', "You cannot search for the association field 'Doctrine\Tests\Models\CMS\CmsUser#address', because it is the inverse side of an association. Find methods only work on owning side associations.");
        $user = $repos->findBy(array('address' => $addressId));
    }

    /**
     * @group DDC-817
     */
    public function testFindOneByAssociationKey()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $address = $repos->findOneBy(array('user' => $userId));

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $address);
        $this->assertEquals($addressId, $address->id);
    }

    /**
     * @group DDC-1241
     */
    public function testFindOneByOrderBy()
    {
    	$this->loadFixture();
    	
    	$repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
    	$userAsc = $repos->findOneBy(array(), array("username" => "ASC"));
    	$userDesc = $repos->findOneBy(array(), array("username" => "DESC"));
    	
    	$this->assertNotSame($userAsc, $userDesc);
    }
    
    /**
     * @group DDC-817
     */
    public function testFindByAssociationKey()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses = $repos->findBy(array('user' => $userId));

        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsAddress', $addresses);
        $this->assertEquals(1, count($addresses));
        $this->assertEquals($addressId, $addresses[0]->id);
    }

    /**
     * @group DDC-817
     */
    public function testFindAssociationByMagicCall()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses = $repos->findByUser($userId);

        $this->assertContainsOnly('Doctrine\Tests\Models\CMS\CmsAddress', $addresses);
        $this->assertEquals(1, count($addresses));
        $this->assertEquals($addressId, $addresses[0]->id);
    }

    /**
     * @group DDC-817
     */
    public function testFindOneAssociationByMagicCall()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $address = $repos->findOneByUser($userId);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsAddress', $address);
        $this->assertEquals($addressId, $address->id);
    }

    public function testValidNamedQueryRetrieval()
    {
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $query = $repos->createNamedQuery('all');

        $this->assertInstanceOf('Doctrine\ORM\Query', $query);
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u', $query->getDQL());
    }

    public function testInvalidNamedQueryRetrieval()
    {
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');

        $repos->createNamedQuery('invalidNamedQuery');
    }

    /**
     * @group DDC-1087
     */
    public function testIsNullCriteriaDoesNotGenerateAParameter()
    {
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repos->findBy(array('status' => null, 'username' => 'romanb'));

        $params = $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['params'];
        $this->assertEquals(1, count($params), "Should only execute with one parameter.");
        $this->assertEquals(array('romanb'), $params);
    }

    public function testIsNullCriteria()
    {
        $this->loadFixture();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findBy(array('status' => null));
        $this->assertEquals(1, count($users));
    }

    /**
     * @group DDC-1094
     */
    public function testFindByLimitOffset()
    {
        $this->loadFixture();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users1 = $repos->findBy(array(), null, 1, 0);
        $users2 = $repos->findBy(array(), null, 1, 1);

        $this->assertEquals(4, count($repos->findBy(array())));
        $this->assertEquals(1, count($users1));
        $this->assertEquals(1, count($users2));
        $this->assertNotSame($users1[0], $users2[0]);
    }

    /**
     * @group DDC-1094
     */
    public function testFindByOrderBy()
    {
        $this->loadFixture();

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $usersAsc = $repos->findBy(array(), array("username" => "ASC"));
        $usersDesc = $repos->findBy(array(), array("username" => "DESC"));

        $this->assertEquals(4, count($usersAsc), "Pre-condition: only four users in fixture");
        $this->assertEquals(4, count($usersDesc), "Pre-condition: only four users in fixture");
        $this->assertSame($usersAsc[0], $usersDesc[3]);
        $this->assertSame($usersAsc[3], $usersDesc[0]);
    }

    /**
     * @group DDC-1376
     */
    public function testFindByOrderByAssociation()
    {
        $this->loadFixtureUserEmail();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $resultAsc  = $repository->findBy(array(), array('email' => 'ASC'));
        $resultDesc = $repository->findBy(array(), array('email' => 'DESC'));

        $this->assertCount(3, $resultAsc);
        $this->assertCount(3, $resultDesc);

        $this->assertEquals($resultAsc[0]->getEmail()->getId(), $resultDesc[2]->getEmail()->getId());
        $this->assertEquals($resultAsc[2]->getEmail()->getId(), $resultDesc[0]->getEmail()->getId());
    }

    /**
     * @group DDC-1426
     */
    public function testFindFieldByMagicCallOrderBy()
    {
        $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $usersAsc = $repos->findByStatus('dev', array('username' => "ASC"));
        $usersDesc = $repos->findByStatus('dev', array('username' => "DESC"));

        $this->assertEquals(2, count($usersAsc));
        $this->assertEquals(2, count($usersDesc));

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser',$usersAsc[0]);
        $this->assertEquals('Alexander', $usersAsc[0]->name);
        $this->assertEquals('dev', $usersAsc[0]->status);

        $this->assertSame($usersAsc[0], $usersDesc[1]);
        $this->assertSame($usersAsc[1], $usersDesc[0]);
    }

    /**
     * @group DDC-1426
     */
    public function testFindFieldByMagicCallLimitOffset()
    {
        $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users1 = $repos->findByStatus('dev', array(), 1, 0);
        $users2 = $repos->findByStatus('dev', array(), 1, 1);

        $this->assertEquals(1, count($users1));
        $this->assertEquals(1, count($users2));
        $this->assertNotSame($users1[0], $users2[0]);
    }

    /**
     * @group DDC-753
     */
    public function testDefaultRepositoryClassName()
    {
        $this->assertEquals($this->_em->getConfiguration()->getDefaultRepositoryClassName(), "Doctrine\ORM\EntityRepository");
        $this->_em->getConfiguration()->setDefaultRepositoryClassName("Doctrine\Tests\Models\DDC753\DDC753DefaultRepository");
        $this->assertEquals($this->_em->getConfiguration()->getDefaultRepositoryClassName(), "Doctrine\Tests\Models\DDC753\DDC753DefaultRepository");

        $repos = $this->_em->getRepository('Doctrine\Tests\Models\DDC753\DDC753EntityWithDefaultCustomRepository');
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC753\DDC753DefaultRepository", $repos);
        $this->assertTrue($repos->isDefaultRepository());


        $repos = $this->_em->getRepository('Doctrine\Tests\Models\DDC753\DDC753EntityWithCustomRepository');
        $this->assertInstanceOf("Doctrine\Tests\Models\DDC753\DDC753CustomRepository", $repos);
        $this->assertTrue($repos->isCustomRepository());

        $this->assertEquals($this->_em->getConfiguration()->getDefaultRepositoryClassName(), "Doctrine\Tests\Models\DDC753\DDC753DefaultRepository");
        $this->_em->getConfiguration()->setDefaultRepositoryClassName("Doctrine\ORM\EntityRepository");
        $this->assertEquals($this->_em->getConfiguration()->getDefaultRepositoryClassName(), "Doctrine\ORM\EntityRepository");

    }

    /**
     * @group DDC-753
     * @expectedException Doctrine\ORM\ORMException
     * @expectedExceptionMessage Invalid repository class 'Doctrine\Tests\Models\DDC753\DDC753InvalidRepository'. It must be a Doctrine\Common\Persistence\ObjectRepository.
     */
    public function testSetDefaultRepositoryInvalidClassError()
    {
        $this->assertEquals($this->_em->getConfiguration()->getDefaultRepositoryClassName(), "Doctrine\ORM\EntityRepository");
        $this->_em->getConfiguration()->setDefaultRepositoryClassName("Doctrine\Tests\Models\DDC753\DDC753InvalidRepository");
    }

    /**
     * @group DDC-1376
     * 
     * @expectedException Doctrine\ORM\ORMException
     * @expectedExceptionMessage You cannot search for the association field 'Doctrine\Tests\Models\CMS\CmsUser#address', because it is the inverse side of an association.
     */
    public function testInvalidOrderByAssociation()
    {
        $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')
            ->findBy(array('status' => 'test'), array('address' => 'ASC'));
    }

    /**
     * @group DDC-1500
     */
    public function testInvalidOrientation()
    {
        $this->setExpectedException('Doctrine\ORM\ORMException', 'Invalid order by orientation specified for Doctrine\Tests\Models\CMS\CmsUser#username');

        $repo = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $repo->findBy(array('status' => 'test'), array('username' => 'INVALID'));
    }

    /**
     * @group DDC-1713
     */
    public function testFindByAssociationArray()
    {
        $repo = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsArticle');
        $data = $repo->findBy(array('user' => array(1, 2, 3)));

        $query = array_pop($this->_sqlLoggerStack->queries);
        $this->assertEquals(array(1,2,3), $query['params'][0]);
        $this->assertEquals(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, $query['types'][0]);
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingEmptyCriteria()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria());

        $this->assertEquals(4, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaEqComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->eq('username', 'beberlei')
        ));

        $this->assertEquals(1, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaNeqComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->neq('username', 'beberlei')
        ));

        $this->assertEquals(3, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaInComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->in('username', array('beberlei', 'gblanco'))
        ));

        $this->assertEquals(2, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaNotInComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->notIn('username', array('beberlei', 'gblanco', 'asm89'))
        ));

        $this->assertEquals(1, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaLtComparison()
    {
        $firstUserId = $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->lt('id', $firstUserId + 1)
        ));

        $this->assertEquals(1, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaLeComparison()
    {
        $firstUserId = $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->lte('id', $firstUserId + 1)
        ));

        $this->assertEquals(2, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaGtComparison()
    {
        $firstUserId = $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->gt('id', $firstUserId)
        ));

        $this->assertEquals(3, count($users));
    }

    /**
     * @group DDC-1637
     */
    public function testMatchingCriteriaGteComparison()
    {
        $firstUserId = $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $users = $repository->matching(new Criteria(
            Criteria::expr()->gte('id', $firstUserId)
        ));

        $this->assertEquals(4, count($users));
    }

    /**
     * @group DDC-2430
     */
    public function testMatchingCriteriaAssocationByObjectInMemory()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();

        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $userId);

        $criteria = new Criteria(
            Criteria::expr()->eq('user', $user)
        );

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses = $repository->matching($criteria);

        $this->assertEquals(1, count($addresses));

        $addresses = new ArrayCollection($repository->findAll());

        $this->assertEquals(1, count($addresses->matching($criteria)));
    }

    /**
     * @group DDC-2430
     */
    public function testMatchingCriteriaAssocationInWithArray()
    {
        list($userId, $addressId) = $this->loadAssociatedFixture();

        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $userId);

        $criteria = new Criteria(
            Criteria::expr()->in('user', array($user))
        );

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsAddress');
        $addresses = $repository->matching($criteria);

        $this->assertEquals(1, count($addresses));

        $addresses = new ArrayCollection($repository->findAll());

        $this->assertEquals(1, count($addresses->matching($criteria)));
    }

    public function testMatchingCriteriaContainsComparison()
    {
        $this->loadFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repository->matching(new Criteria(Criteria::expr()->contains('name', 'Foobar')));
        $this->assertEquals(0, count($users));

        $users = $repository->matching(new Criteria(Criteria::expr()->contains('name', 'Rom')));
        $this->assertEquals(1, count($users));

        $users = $repository->matching(new Criteria(Criteria::expr()->contains('status', 'dev')));
        $this->assertEquals(2, count($users));
    }

    /**
     * @group DDC-2478
     */
    public function testMatchingCriteriaNullAssocComparison()
    {
        $fixtures       = $this->loadFixtureUserEmail();
        $user           = $this->_em->merge($fixtures[0]);
        $repository     = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $criteriaIsNull = Criteria::create()->where(Criteria::expr()->isNull('email'));
        $criteriaEqNull = Criteria::create()->where(Criteria::expr()->eq('email', null));

        $user->setEmail(null);
        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $usersIsNull = $repository->matching($criteriaIsNull);
        $usersEqNull = $repository->matching($criteriaEqNull);

        $this->assertCount(1, $usersIsNull);
        $this->assertCount(1, $usersEqNull);

        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $usersIsNull[0]);
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsUser', $usersEqNull[0]);

        $this->assertNull($usersIsNull[0]->getEmail());
        $this->assertNull($usersEqNull[0]->getEmail());
    }

    /**
     * @group DDC-2055
     */
    public function testCreateResultSetMappingBuilder()
    {
        $repository = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');
        $rsm = $repository->createResultSetMappingBuilder('u');

        $this->assertInstanceOf('Doctrine\ORM\Query\ResultSetMappingBuilder', $rsm);
        $this->assertEquals(array('u' => 'Doctrine\Tests\Models\CMS\CmsUser'), $rsm->aliasMap);
    }
}

