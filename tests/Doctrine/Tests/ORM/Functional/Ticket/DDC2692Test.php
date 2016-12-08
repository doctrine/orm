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
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC2692Foo::class),
                ]
            );
        } catch(\Exception $e) {
            return;
        }
        $this->_em->clear();
    }

    public function testIsListenerCalledOnlyOnceOnPreFlush()
    {
        $listener = $this->getMockBuilder(DDC2692Listener::class)
                         ->setMethods(['preFlush'])
                         ->getMock();

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
        return [\Doctrine\ORM\Events::preFlush];
    }

    public function preFlush(PreFlushEventArgs $args) {
    }
}
