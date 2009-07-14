<?php

namespace Doctrine\Tests\Common;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventArgs;

require_once __DIR__ . '/../TestInit.php';

class EventManagerTest extends \Doctrine\Tests\DoctrineTestCase
{
    /* Some pseudo events */
    const preFoo = 'preFoo';
    const postFoo = 'postFoo';
    const preBar = 'preBar';
    const postBar = 'postBar';

    private $_preFooInvoked = false;
    private $_postFooInvoked = false;

    private $_eventManager;

    protected function setUp()
    {
        $this->_eventManager = new EventManager;
        $this->_preFooInvoked = false;
        $this->_postFooInvoked = false;
    }

    public function testInitialState()
    {
        $this->assertEquals(array(), $this->_eventManager->getListeners());
        $this->assertFalse($this->_eventManager->hasListeners(self::preFoo));
        $this->assertFalse($this->_eventManager->hasListeners(self::postFoo));
    }

    public function testAddEventListener()
    {
        $this->_eventManager->addEventListener(array('preFoo', 'postFoo'), $this);
        $this->assertTrue($this->_eventManager->hasListeners(self::preFoo));
        $this->assertTrue($this->_eventManager->hasListeners(self::postFoo));
        $this->assertEquals(1, count($this->_eventManager->getListeners(self::preFoo)));
        $this->assertEquals(1, count($this->_eventManager->getListeners(self::postFoo)));
        $this->assertEquals(2, count($this->_eventManager->getListeners()));
    }

    public function testDispatchEvent()
    {
        $this->_eventManager->addEventListener(array('preFoo', 'postFoo'), $this);
        $this->_eventManager->dispatchEvent(self::preFoo);
        $this->assertTrue($this->_preFooInvoked);
        $this->assertFalse($this->_postFooInvoked);
    }

    public function testRemoveEventListener()
    {
        $this->_eventManager->addEventListener(array('preBar'), $this);
        $this->assertTrue($this->_eventManager->hasListeners(self::preBar));
        $this->_eventManager->removeEventListener(array('preBar'), $this);
        $this->assertFalse($this->_eventManager->hasListeners(self::preBar));
    }

    public function testAddEventSubscriber()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->_eventManager->addEventSubscriber($eventSubscriber);
        $this->assertTrue($this->_eventManager->hasListeners(self::preFoo));
        $this->assertTrue($this->_eventManager->hasListeners(self::postFoo));
    }

    /* Listener methods */

    public function preFoo(EventArgs $e)
    {
        $this->_preFooInvoked = true;
    }

    public function postFoo(EventArgs $e)
    {
        $this->_postFooInvoked = true;
    }
}

class TestEventSubscriber implements \Doctrine\Common\EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array('preFoo', 'postFoo');
    }
}