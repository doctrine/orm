<?php

namespace Shitty\Tests\ORM\Functional;

use Shitty\ORM\Event\OnClearEventArgs;
use Shitty\ORM\Events;

/**
 * ClearEventTest
 *
 * @author Michael Ridgway <mcridgway@gmail.com>
 */
class ClearEventTest extends \Shitty\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
    }

    public function testEventIsCalledOnClear()
    {
        $listener = new OnClearListener;
        $this->_em->getEventManager()->addEventListener(Events::onClear, $listener);

        $this->_em->clear();

        $this->assertTrue($listener->called);
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
