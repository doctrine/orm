<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyFixContract;

/**
* @group DDC-1955
*/
class EntityListenersTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @var \Doctrine\Tests\Models\Company\CompanyContractListener
     */
    private $listener;

    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();

        $this->listener = $this->_em->getConfiguration()
            ->getEntityListenerResolver()
            ->resolve('Doctrine\Tests\Models\Company\CompanyContractListener');
    }

    public function testPreFlushListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->preFlushCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->preFlushCalls);

        $this->assertSame($fix, $this->listener->preFlushCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->preFlushCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreFlushEventArgs',
            $this->listener->preFlushCalls[0][1]
        );
    }

    public function testPostLoadListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);
        
        $this->_em->persist($fix);
        $this->_em->flush();
        $this->_em->clear();

        $this->listener->postLoadCalls  = array();

        $dql = "SELECT f FROM Doctrine\Tests\Models\Company\CompanyFixContract f WHERE f.id = ?1";
        $fix = $this->_em->createQuery($dql)->setParameter(1, $fix->getId())->getSingleResult();

        $this->assertCount(1,$this->listener->postLoadCalls);

        $this->assertSame($fix, $this->listener->postLoadCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->postLoadCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->postLoadCalls[0][1]
        );
    }

    public function testPrePersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->prePersistCalls  = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->prePersistCalls);

        $this->assertSame($fix, $this->listener->prePersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->prePersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->prePersistCalls[0][1]
        );
    }

    public function testPostPersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->postPersistCalls = array();

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->postPersistCalls);

        $this->assertSame($fix, $this->listener->postPersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->postPersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->postPersistCalls[0][1]
        );
    }

    public function testPreUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->preUpdateCalls = array();
        
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->preUpdateCalls);

        $this->assertSame($fix, $this->listener->preUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->preUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\PreUpdateEventArgs',
            $this->listener->preUpdateCalls[0][1]
        );
    }

    public function testPostUpdateListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->postUpdateCalls = array();

        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->postUpdateCalls);

        $this->assertSame($fix, $this->listener->postUpdateCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->postUpdateCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->postUpdateCalls[0][1]
        );
    }

    public function testPreRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->preRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->preRemoveCalls);

        $this->assertSame($fix, $this->listener->preRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->preRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->preRemoveCalls[0][1]
        );
    }

    public function testPostRemoveListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->postRemoveCalls = array();

        $this->_em->remove($fix);
        $this->_em->flush();

        $this->assertCount(1,$this->listener->postRemoveCalls);

        $this->assertSame($fix, $this->listener->postRemoveCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->postRemoveCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->postRemoveCalls[0][1]
        );
    }
}