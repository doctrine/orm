<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../TestInit.php';

/**
 * ClearEventTest
 *
 * @author Michael Ridgway <mcridgway@gmail.com>
 */
class ClearEventTest extends \Doctrine\Tests\OrmFunctionalTestCase
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