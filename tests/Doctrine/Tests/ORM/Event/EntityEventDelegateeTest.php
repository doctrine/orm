<?php

namespace Doctrine\Tests\ORM\Event;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @group DDC-1415
 */
class EntityEventDelegatorTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Event\EntityEventDelegator
     */
    private $delegator;

    public function setUp()
    {
        $this->delegator = new \Doctrine\ORM\Event\EntityEventDelegator();
    }

    public function testGetSubscribedEventsWhenEmpty()
    {
        $this->assertEquals(array(), $this->delegator->getSubscribedEvents());
    }

    public function testAddListener()
    {
        $this->delegator->addEventListener('postLoad', 'stdClass', new DelegateeEventListener());
        $this->assertEquals(array('postLoad'), $this->delegator->getSubscribedEvents());
    }

    public function testAddSubscriber()
    {
        $this->delegator->addEventSubscriber(new DelegateeEventListener(), 'stdClass');
        $this->assertEquals(array('postLoad'), $this->delegator->getSubscribedEvents());
    }

    public function testAddListenerAfterFrozenThrowsException()
    {
        $this->delegator->getSubscribedEvents(); // freezes

        $this->setExpectedException("LogicException", "Cannot add event listeners aft");
        $this->delegator->addEventListener('postLoad', 'stdClass', new DelegateeEventListener());
    }

    public function testDelegateEvent()
    {
        $delegatee = new DelegateeEventListener();
        $this->delegator->addEventListener('postLoad', 'stdClass', $delegatee);

        $event = new \Doctrine\ORM\Event\LifecycleEventArgs(new \stdClass(), $this->_getTestEntityManager());
        $this->delegator->postLoad($event);
        $this->delegator->postLoad($event);

        $this->assertEquals(2, $delegatee->postLoad);
    }

    public function testDelegatePickEntity()
    {
        $delegatee = new DelegateeEventListener();
        $this->delegator->addEventListener('postLoad', 'stdClass', $delegatee);

        $event1 = new \Doctrine\ORM\Event\LifecycleEventArgs(new \stdClass(), $this->_getTestEntityManager());
        $event2 = new \Doctrine\ORM\Event\LifecycleEventArgs(new \Doctrine\Tests\Models\CMS\CmsUser(), $this->_getTestEntityManager());
        $this->delegator->postLoad($event1);
        $this->delegator->postLoad($event2);

        $this->assertEquals(1, $delegatee->postLoad);
    }
}

class DelegateeEventListener implements \Doctrine\Common\EventSubscriber
{
    public $postLoad = 0;

    public function postLoad($args)
    {
        $this->postLoad++;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    function getSubscribedEvents()
    {
        return array('postLoad');
    }
}