<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-2996
 */
class DDC2996Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testIssue()
    {
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC2996User::class),
            $this->_em->getClassMetadata(DDC2996UserPreference::class),
            ]
        );

        $pref = new DDC2996UserPreference();
        $pref->user = new DDC2996User();
        $pref->value = "foo";

        $this->_em->persist($pref);
        $this->_em->persist($pref->user);
        $this->_em->flush();

        $pref->value = "bar";
        $this->_em->flush();

        $this->assertEquals(1, $pref->user->counter);

        $this->_em->clear();

        $pref = $this->_em->find(DDC2996UserPreference::class, $pref->id);
        $this->assertEquals(1, $pref->user->counter);
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
