<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFixContract;

/**
 * @group DDC-1955
 */
class EntityListenersOnMergeTest extends \Doctrine\Tests\OrmFunctionalTestCase
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

    public function testPrePersistListeners()
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->prePersistCalls = [];

        $fix = $this->_em->merge($fix);
        $this->_em->flush();

        $this->assertCount(1, $this->listener->prePersistCalls);

        $this->assertSame($fix, $this->listener->prePersistCalls[0][0]);

        $this->assertInstanceOf(
            'Doctrine\Tests\Models\Company\CompanyFixContract',
            $this->listener->prePersistCalls[0][0]
        );

        $this->assertInstanceOf(
            'Doctrine\ORM\Event\LifecycleEventArgs',
            $this->listener->prePersistCalls[0][1]
        );

        $this->assertArrayHasKey('fixPrice', $this->listener->snapshots[CompanyContractListener::PRE_PERSIST][0]);
        $this->assertEquals(
            $fix->getFixPrice(),
            $this->listener->snapshots[CompanyContractListener::PRE_PERSIST][0]['fixPrice']
        );
    }
}