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
     * Verify that when a flush is called in postUpdate, if any listeners exist on preUpdate and no changes have been
     * made to the entity, the third argument passed into PreUpdateEventArgs is a valid argument type
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
        $this->_em->clear();

        $people = [
            $person->id,
            $person2->id
        ];

        foreach ($people as $person) {
            /** @var GH6884Person $managedPerson */
            $managedPerson = $this->_em->find(GH6884Person::class, $person);

            $managedPerson->isAlive = true;
        }

        $this->_em->flush();
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

    /**
     * @param GH6884Person       $person
     * @param LifecycleEventArgs $eventArgs
     *
     * @return void
     */
    public function onPostUpdate(GH6884Person $person, LifecycleEventArgs $eventArgs)
    {
        $eventArgs->getEntityManager()->flush();
    }
}
