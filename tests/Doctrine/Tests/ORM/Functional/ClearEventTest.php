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
    public function testEventIsCalledOnClear(): void
    {
        $listener = new OnClearListener();
        $this->_em->getEventManager()->addEventListener(Events::onClear, $listener);

        $this->_em->clear();

        self::assertTrue($listener->called);
    }
}

class OnClearListener
{
    /** @var bool */
    public $called = false;

    public function onClear(OnClearEventArgs $args): void
    {
        $this->called = true;
    }
}
