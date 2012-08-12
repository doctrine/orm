<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyContractListener;

require_once __DIR__ . '/../../TestInit.php';

/**
* @group DDC-1955
*/
class EntityListenersTest extends \Doctrine\Tests\OrmFunctionalTestCase
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

        CompanyContractListener::$preFlushCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$preFlushCalls);

        $this->assertSame($fix, CompanyContractListener::$preFlushCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$preFlushCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreFlushEventArgs',
            CompanyContractListener::$preFlushCalls[0][1]
        );
    }

    public function testPostLoadListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);
        
        $this->_em->persist($fix);
        $this->_em->flush();
        $this->_em->clear();

        CompanyContractListener::$postLoadCalls  = array();

        $dql = "SELECT f FROM Doctrine\Tests\Models\Company\CompanyFixContract f WHERE f.id = ?1";
        $fix = $this->_em->createQuery($dql)->setParameter(1, $fix->getId())->getSingleResult();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$postLoadCalls);

        $this->assertSame($fix, CompanyContractListener::$postLoadCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$postLoadCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$postLoadCalls[0][1]
        );
    }

    public function testPrePersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        CompanyContractListener::$prePersistCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$prePersistCalls);

        $this->assertSame($fix, CompanyContractListener::$prePersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$prePersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$prePersistCalls[0][1]
        );
    }

    public function testPostPersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        CompanyContractListener::$postPersistCalls = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$postPersistCalls);

        $this->assertSame($fix, CompanyContractListener::$postPersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$postPersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$postPersistCalls[0][1]
        );
    }

    public function testPreUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        CompanyContractListener::$preUpdateCalls = array();
        
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$preUpdateCalls);

        $this->assertSame($fix, CompanyContractListener::$preUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$preUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreUpdateEventArgs',
            CompanyContractListener::$preUpdateCalls[0][1]
        );
    }

    public function testPostUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        CompanyContractListener::$postUpdateCalls = array();

        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$postUpdateCalls);

        $this->assertSame($fix, CompanyContractListener::$postUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$postUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$postUpdateCalls[0][1]
        );
    }

    public function testPreRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        CompanyContractListener::$preRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$preRemoveCalls);

        $this->assertSame($fix, CompanyContractListener::$preRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$preRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$preRemoveCalls[0][1]
        );
    }

    public function testPostRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        CompanyContractListener::$postRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,CompanyContractListener::$instances);
        $this->assertCount(1,CompanyContractListener::$postRemoveCalls);

        $this->assertSame($fix, CompanyContractListener::$postRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            CompanyContractListener::$postRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            CompanyContractListener::$postRemoveCalls[0][1]
        );
    }
}