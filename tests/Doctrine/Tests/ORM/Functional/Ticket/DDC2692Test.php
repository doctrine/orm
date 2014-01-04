<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;

/**
 * @group DDC-2692
 */
class DDC2692Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2692Foo'),
            ));
        } catch(\Exception $e) {
            return;
        }
        $this->_em->clear();
    }

    public function testIsListenerCalledOnlyOnceOnPreFlush()
    {
        $listener = $this->getMock('Doctrine\Tests\ORM\Functional\Ticket\DDC2692Listener', array('preFlush'));
        $listener->expects($this->once())->method('preFlush');

        $this->_em->getEventManager()->addEventSubscriber($listener);

        $this->_em->persist(new DDC2692Foo);
        $this->_em->persist(new DDC2692Foo);

        $this->_em->flush();
        $this->_em->clear();
    }
}
/**
 * @Entity @Table(name="ddc_2692_foo")
 */
class DDC2692Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

class DDC2692Listener implements EventSubscriber {

    public function getSubscribedEvents() {
        return array(\Doctrine\ORM\Events::preFlush);
    }

    public function preFlush(PreFlushEventArgs $args) {
    }
}


