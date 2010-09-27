<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Description of DetachedEntityTest
 *
 * @author robo
 */
class EntityRepositoryTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
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

        $this->_em->flush();
        $user1Id = $user->getId();
        unset($user);
        unset($user2);
        $this->_em->clear();

        return $user1Id;
    }

    public function testBasicFind()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $user = $repos->find($user1Id);
        $this->assertTrue($user instanceof CmsUser);
        $this->assertEquals('Roman', $user->name);
        $this->assertEquals('freak', $user->status);
    }

    public function testFindByField()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findBy(array('status' => 'dev'));
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);
    }


    public function testFindFieldByMagicCall()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findByStatus('dev');
        $this->assertEquals(1, count($users));
        $this->assertTrue($users[0] instanceof CmsUser);
        $this->assertEquals('Guilherme', $users[0]->name);
        $this->assertEquals('dev', $users[0]->status);
    }
    
    public function testFindAll()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findAll();
        $this->assertEquals(2, count($users));
    }

    public function testFindByAlias()
    {
        $user1Id = $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $this->_em->getConfiguration()->addEntityNamespace('CMS', 'Doctrine\Tests\Models\CMS');

        $repos = $this->_em->getRepository('CMS:CmsUser');

        $users = $repos->findAll();
        $this->assertEquals(2, count($users));
    }

    public function tearDown()
    {
        $this->_em->getConfiguration()->setEntityNamespaces(array());
        parent::tearDown();
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

        $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $userId);
        
        $this->setExpectedException('Doctrine\ORM\OptimisticLockException');
        $this->_em->find('Doctrine\Tests\Models\Cms\CmsUser', $userId, \Doctrine\DBAL\LockMode::OPTIMISTIC);
    }

    /**
     * @group DDC-819
     */
    public function testFindMagicCallByNullValue()
    {
        $this->loadFixture();
        $repos = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser');

        $users = $repos->findByStatus(null);
        $this->assertEquals(0, count($users));
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
}

