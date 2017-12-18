<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-6884
 */
final class GH6884Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6884Person::class)
            ]
        );

        $this->_em->getEventManager()->addEventListener(
            [Events::preUpdate],
            $this
        );

        $this->_em->getClassMetadata(GH6884Person::class)
                  ->addEntityListener(Events::postUpdate, GH6884Person::class, 'onPostUpdate');
    }

    /**
     * Verify that firing the PreUpdate event succeeds when a flush is called after an iteration and modification
     * of entities that have a PostUpdate event which calls flush with no entity changes
     *
     * @return void
     */
    public function testIssue()
    {
        $person  = new GH6884Person();
        $person2 = new GH6884Person();

        $this->_em->persist($person);
        $this->_em->persist($person2);
        $this->_em->flush();

        $people = [
            $person,
            $person2
        ];

        foreach ($people as $person) {
            $person->isAlive = true;
        }

        $this->_em->flush();

        foreach ($people as $person) {
            $this->assertTrue($person->nonOrmProperty);
        }
    }

    /**
     * @param PreUpdateEventArgs $eventArgs
     *
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        // preUpdate logic
    }
}

/**
 * @Entity()
 */
class GH6884Person
{
    /** @Id() @Column(type="integer") @GeneratedValue() */
    public $id;

    /** @Column(type="boolean", nullable=false) */
    public $isAlive = false;

    /** @var bool */
    public $nonOrmProperty = false;

    /**
     * @param GH6884Person       $person
     * @param LifecycleEventArgs $eventArgs
     *
     * @return void
     */
    public function onPostUpdate(GH6884Person $person, LifecycleEventArgs $eventArgs)
    {
        $person->nonOrmProperty = true;

        $eventArgs->getEntityManager()->flush();
    }
}
