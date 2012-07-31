<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\ContractSubscriber;

require_once __DIR__ . '/../../TestInit.php';

/**
* @group DDC-1955
*/
class EntityListenersDispatcherTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    public function testPreFlushListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        ContractSubscriber::$preFlushCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$preFlushCalls);

        $this->assertSame($fix, ContractSubscriber::$preFlushCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$preFlushCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreFlushEventArgs',
            ContractSubscriber::$preFlushCalls[0][1]
        );
    }

    public function testPostLoadListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);
        
        $this->_em->persist($fix);
        $this->_em->flush();
        $this->_em->clear();

        ContractSubscriber::$postLoadCalls  = array();

        $dql = "SELECT f FROM Doctrine\Tests\Models\Company\CompanyFixContract f WHERE f.id = ?1";
        $fix = $this->_em->createQuery($dql)->setParameter(1, $fix->getId())->getSingleResult();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$postLoadCalls);

        $this->assertSame($fix, ContractSubscriber::$postLoadCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$postLoadCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$postLoadCalls[0][1]
        );
    }

    public function testPrePersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        ContractSubscriber::$prePersistCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$prePersistCalls);

        $this->assertSame($fix, ContractSubscriber::$prePersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$prePersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$prePersistCalls[0][1]
        );
    }

    public function testPostPersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        ContractSubscriber::$postPersistCalls = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$postPersistCalls);

        $this->assertSame($fix, ContractSubscriber::$postPersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$postPersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$postPersistCalls[0][1]
        );
    }

    public function testPreUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        ContractSubscriber::$preUpdateCalls = array();
        
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$preUpdateCalls);

        $this->assertSame($fix, ContractSubscriber::$preUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$preUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreUpdateEventArgs',
            ContractSubscriber::$preUpdateCalls[0][1]
        );
    }

    public function testPostUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        ContractSubscriber::$postUpdateCalls = array();

        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$postUpdateCalls);

        $this->assertSame($fix, ContractSubscriber::$postUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$postUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$postUpdateCalls[0][1]
        );
    }

    public function testPreRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        ContractSubscriber::$preRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$preRemoveCalls);

        $this->assertSame($fix, ContractSubscriber::$preRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$preRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$preRemoveCalls[0][1]
        );
    }

    public function testPostRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        ContractSubscriber::$postRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,ContractSubscriber::$instances);
        $this->assertCount(1,ContractSubscriber::$postRemoveCalls);

        $this->assertSame($fix, ContractSubscriber::$postRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            ContractSubscriber::$postRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            ContractSubscriber::$postRemoveCalls[0][1]
        );
    }
}