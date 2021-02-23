<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * @group DDC-2692
 */
class DDC2692Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC2692Foo::class),
                ]
            );
        } catch (Exception $e) {
            return;
        }
        $this->em->clear();
    }

    public function testIsListenerCalledOnlyOnceOnPreFlush() : void
    {
        $listener = $this->getMockBuilder(DDC2692Listener::class)
                         ->setMethods(['preFlush'])
                         ->getMock();

        $listener->expects($this->once())->method('preFlush');

        $this->em->getEventManager()->addEventSubscriber($listener);

        $this->em->persist(new DDC2692Foo());
        $this->em->persist(new DDC2692Foo());

        $this->em->flush();
        $this->em->clear();
    }
}
/**
 * @ORM\Entity @ORM\Table(name="ddc_2692_foo")
 */
class DDC2692Foo
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

class DDC2692Listener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [Events::preFlush];
    }

    public function preFlush(PreFlushEventArgs $args)
    {
    }
}
