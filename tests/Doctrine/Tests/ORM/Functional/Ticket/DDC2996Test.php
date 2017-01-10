<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-2996
 */
class DDC2996Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2996User::class),
            $this->em->getClassMetadata(DDC2996UserPreference::class),
            ]
        );

        $pref = new DDC2996UserPreference();
        $pref->user = new DDC2996User();
        $pref->value = "foo";

        $this->em->persist($pref);
        $this->em->persist($pref->user);
        $this->em->flush();

        $pref->value = "bar";
        $this->em->flush();

        self::assertEquals(1, $pref->user->counter);

        $this->em->clear();

        $pref = $this->em->find(DDC2996UserPreference::class, $pref->id);
        self::assertEquals(1, $pref->user->counter);
    }
}

/**
 * @Entity
 */
class DDC2996User
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    public $id;
    /**
     * @Column(type="integer")
     */
    public $counter = 0;
}

/**
 * @Entity @HasLifecycleCallbacks
 */
class DDC2996UserPreference
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $value;

    /**
     * @ManyToOne(targetEntity="DDC2996User")
     */
    public $user;

    /**
     * @PreFlush
     */
    public function preFlush($event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($uow->getOriginalEntityData($this->user)) {
            $this->user->counter++;
            $uow->recomputeSingleEntityChangeSet(
                $em->getClassMetadata(get_class($this->user)),
                $this->user
            );
        }
    }
}
