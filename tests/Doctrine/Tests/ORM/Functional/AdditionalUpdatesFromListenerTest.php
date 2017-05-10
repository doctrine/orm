<?php

namespace Doctrine\Tests\ORM\Functional;

class AdditionalUpdatesFromListenerTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testUoWDoesNotThrowErrorWhenUpdatingSecondEntity()
    {
        $otherEntity = new AUFL_OtherEntity();
        $this->_em->persist($otherEntity);
        $this->_em->flush($otherEntity);
        $this->_em->clear();

        $triggeringEntity = new AUFL_TriggeringEntity();
        $triggeringEntity->relatedId = $otherEntity->id;
        $this->_em->persist($triggeringEntity);

        $depTriggeringEntity = new AUFL_TriggeringEntity();
        $depTriggeringEntity->deps->add($triggeringEntity);
        $this->_em->persist($depTriggeringEntity);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->getConnection()->beginTransaction();
        $triggeringEntity = $this->_em->find('Doctrine\Tests\ORM\Functional\AUFL_TriggeringEntity', $triggeringEntity->id);
        foreach ($this->_em->createQuery("SELECT e FROM Doctrine\Tests\ORM\Functional\AUFL_TriggeringEntity e WHERE :e MEMBER OF e.deps")
                ->setParameter('e', $triggeringEntity)->getResult() as $depEntity) {
            $depEntity->state = 'failed';
            $this->_em->persist($depEntity);
        }
        $triggeringEntity->state = 'failed';
        $this->_em->persist($triggeringEntity);
        $this->_em->flush();
        $this->_em->getConnection()->commit();

        $this->_em->clear();

        $this->assertEquals('failed', $this->_em->find('Doctrine\Tests\ORM\Functional\AUFL_TriggeringEntity', $triggeringEntity->id)->state);
        $this->assertEquals('failed', $this->_em->find('Doctrine\Tests\ORM\Functional\AUFL_OtherEntity', $otherEntity->id)->state);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\AUFL_TriggeringEntity'),
            $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\AUFL_OtherEntity'),
        ));

        $this->_em->getEventManager()->addEventListener(array('preUpdate', 'postUpdate'), new AUFL_Listener());
    }
}

class AUFL_Listener
{
    public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs $event)
    {
        // This method is just here to make sure the PreUpdate event is being
        // triggered by the UoW.
    }

    public function postUpdate(\Doctrine\ORM\Event\LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        switch (true) {
            case $entity instanceof AUFL_TriggeringEntity:
                if ('failed' === $entity->state) {
                    $em = $event->getEntityManager();
                    $otherEntity = $em->createQuery("SELECT e FROM Doctrine\Tests\ORM\Functional\AUFL_OtherEntity e WHERE e.id = :id")
                            ->setParameter('id', $entity->relatedId)
                            ->getOneOrNullResult();
                    if ($otherEntity !== null) {
                        $otherEntity->state = 'failed';
                        $em->persist($otherEntity);
                        $em->flush();
                    }

                }
                break;
        }
    }
}

/**
 * @Entity
 */
class AUFL_TriggeringEntity
{
    /** @Id @GeneratedValue(strategy = "AUTO") @Column(type = "integer") */
    public $id;

    /** @Column(type = "integer", nullable = true) */
    public $relatedId;

    /** @Column(type = "string") */
    public $state = 'new';

    /**
     * @ManyToMany(targetEntity = "AUFL_TriggeringEntity", fetch = "EAGER")
     * @JoinTable(name="deps",
     *     joinColumns = { @JoinColumn(name = "source_id", referencedColumnName = "id") },
     *     inverseJoinColumns = { @JoinColumn(name = "dest_id", referencedColumnName = "id")}
     * )
     */
    public $deps;

    public function __construct()
    {
        $this->deps = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @Entity
 */
class AUFL_OtherEntity
{
    /** @Id @GeneratedValue(strategy = "AUTO") @Column(type = "integer") */
    public $id;

    /** @Column(type = "string") */
    public $state = 'new';
}