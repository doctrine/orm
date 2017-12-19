<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\OrmFunctionalTestCase;
use stdClass;

/**
 * @group 6884
 */
final class GH6884Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6884Person::class)
            ]
        );

        $listener = $this->getMock(stdClass::class, ['preUpdate']);

        $listener->expects($this->atLeastOnce())->method('preUpdate');

        $this->_em->getEventManager()->addEventListener([Events::preUpdate], $listener);

        $this->_em->getClassMetadata(GH6884Person::class)
                  ->addEntityListener(Events::postUpdate, GH6884Person::class, 'onPostUpdate');
    }

    public function testIssue(): void
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

    public function onPostUpdate(GH6884Person $person, LifecycleEventArgs $eventArgs): void
    {
        $person->nonOrmProperty = true;

        $eventArgs->getEntityManager()->flush();
    }
}
