<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyFlexContract;
use Doctrine\Tests\Models\Company\ContractSubscriber;

require_once __DIR__ . '/../../TestInit.php';

class EntityListenersDispatcherTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    /**
     * @group DDC-1955
     */
    public function testEntityListeners()
    {
        $flexClass  = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyFixContract');
        $fixClass   = $this->_em->getClassMetadata('Doctrine\Tests\Models\Company\CompanyFlexContract');

        $this->assertNull(ContractSubscriber::$instances);
        $this->assertNull(ContractSubscriber::$prePersistCalls);
        $this->assertNull(ContractSubscriber::$postPersisCalls);

        $fix        = new CompanyFixContract();
        $fixArg     = new LifecycleEventArgs($fix, $this->_em);

        $flex       = new CompanyFlexContract();
        $flexArg    = new LifecycleEventArgs($fix, $this->_em);

        $fixClass->dispatchEntityListeners(Events::prePersist, $fix, $fixArg);
        $flexClass->dispatchEntityListeners(Events::prePersist, $flex, $flexArg);

        $this->assertSame($fix, ContractSubscriber::$prePersistCalls[0][0]);
        $this->assertSame($fixArg, ContractSubscriber::$prePersistCalls[0][1]);

        $this->assertCount(1, ContractSubscriber::$instances);
        $this->assertNull(ContractSubscriber::$postPersisCalls);
    }
}