<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * @group GH8337
 */
class GH8337Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(GH8337Foo::class),
            ]);
        } catch (Exception $e) {
            return;
        }

        $this->_em->clear();
    }

    public function testIsListenerCalledOnlyOnceOnPreFlush()
    {
        $listener = $this->getMockBuilder(GH8337Listener::class)->setMethods(['preFlush'])->getMock();

        $listener->expects($this->once())->method('preFlush');

        $this->_em->getEventManager()->addEventSubscriber($listener);

        $this->_em->persist(new GH8337Foo());
        $this->_em->flush();
        $this->_em->flush();

        $this->_em->clear();
    }

    public function testIsListenerCalledTwiceOnPreFlush()
    {
        $listener = $this->getMockBuilder(GH8337Listener::class)->setMethods(['preFlush'])->getMock();

        $listener->expects($this->exactly(2))->method('preFlush');

        $this->_em->getEventManager()->addEventSubscriber($listener);

        $entity = new GH8337Foo();

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->clear();
    }
}
/**
 * @Entity @Table(name="gh_8337_foo")
 */
class GH8337Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

class GH8337Listener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [Events::preFlush];
    }

    public function preFlush(PreFlushEventArgs $args)
    {
    }
}
