<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

class Ticket5848Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $collector;
    private $listener;

    protected function setUp()
    {
        parent::setUp();
        $this->collector = new Ticket5848EntityCollector();
        $this->listener  = new Ticket5848PreFlushListener();

        $this->listener->collector = $this->collector;

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\Ticket5848Authentication'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\Ticket5848Client'),
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }


        $this->_em->getEventManager()->addEventListener(Events::preFlush, $this->listener);
    }

    public function testExplicitFlushWithOne()
    {
        $e = new Ticket5848Client();

        $this->_em->persist($e);
        $this->_em->flush($e);

        self::assertSame($e, $this->collector->entities[0]);
    }

    public function testExplicitFlushWithMany()
    {
        $e1 = new Ticket5848Client();
        $e2 = new Ticket5848Authentication();
        $e3 = new Ticket5848Client();

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->persist($e3);
        $this->_em->flush([$e1, $e2]);

        self::assertSame($e1, $this->collector->entities[0]);
        self::assertSame($e2, $this->collector->entities[1]);
    }

    public function testExplicitFlushWithConsecutiveFlushes()
    {
        $e1 = new Ticket5848Client();
        $e2 = new Ticket5848Authentication();
        $e3 = new Ticket5848Client();

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->persist($e3);
        $this->_em->flush([$e1, $e2]);
        $this->_em->flush($e3);

        self::assertSame($e3, $this->collector->entities[0]);
    }

    public function testImplicitFlush()
    {
        $e1 = new Ticket5848Client();
        $e2 = new Ticket5848Authentication();
        $e3 = new Ticket5848Client();

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->persist($e3);
        $this->_em->flush();

        self::assertSame($e1, $this->collector->entities[0]);
        self::assertSame($e2, $this->collector->entities[1]);
        self::assertSame($e3, $this->collector->entities[2]);
    }

    public function testExplicitFlushAfterImplicitFlush()
    {
        $e1 = new Ticket5848Client();
        $e2 = new Ticket5848Authentication();
        $e3 = new Ticket5848Client();

        $this->_em->persist($e1);
        $this->_em->persist($e2);
        $this->_em->persist($e3);
        $this->_em->flush();

        self::assertSame($e1, $this->collector->entities[0]);
        self::assertSame($e2, $this->collector->entities[1]);
        self::assertSame($e3, $this->collector->entities[2]);

        $e2->username = 'foo';

        $this->_em->flush([$e2, $e3]);

        self::assertSame($e2, $this->collector->entities[0]);
        self::assertSame($e3, $this->collector->entities[1]);
    }
}

class Ticket5848PreFlushListener
{
    public $collector;

    public function preFlush(PreFlushEventArgs $event)
    {
        if (!$event->hasEntities()) {
            $uow = $event->getEntityManager()->getUnitOfWork();
            $uow->computeChangeSets();

            $entities = array_values(array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()));
        } else {
            $entities = $event->getEntities();
        }

        $this->collector->entities = $entities;
    }
}

class Ticket5848EntityCollector
{
    public $entities;
}

/**
 * @Entity
 * @Table(name="ticket_2481_authentications")
 */
class Ticket5848Authentication
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @column(type="string", nullable=true)
     */
    public $username;
}

/**
 * @Entity
 * @Table(name="ticket_2481_clients")
 */
class Ticket5848Client
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}
