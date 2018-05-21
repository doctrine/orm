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

        $this->schemaTool->createSchema([$this->_em->getClassMetadata(GH6884Person::class)]);

        $listener = $this->createPartialMock(stdClass::class, ['preUpdate']);

        $listener->expects($this->exactly(3))->method('preUpdate');

        $this->em->getEventManager()->addEventListener([Events::preUpdate], $listener);

        $this->em->getClassMetadata(GH6884Person::class)
                  ->addEntityListener(Events::postUpdate, GH6884Person::class, 'onPostUpdate');
    }

    public function testIssue(): void
    {
        $person  = new GH6884Person();
        $person2 = new GH6884Person();

        $this->em->persist($person);
        $this->em->persist($person2);
        $this->em->flush();

        $person->isAlive  = true;
        $person2->isAlive = true;

        $this->em->flush();
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
