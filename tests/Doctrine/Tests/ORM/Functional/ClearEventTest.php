<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * ClearEventTest
 */
class ClearEventTest extends OrmFunctionalTestCase
{
    public function testEventIsCalledOnClear() : void
    {
        $listener = new OnClearListener();
        $this->em->getEventManager()->addEventListener(Events::onClear, $listener);

        $this->em->clear();

        self::assertTrue($listener->called);
    }
}

class OnClearListener
{
    public $called = false;

    public function onClear(OnClearEventArgs $args)
    {
        $this->called = true;
    }
}
