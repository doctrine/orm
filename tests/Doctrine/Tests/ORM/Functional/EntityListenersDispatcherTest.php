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
        $this->markTestIncomplete();
    }
}