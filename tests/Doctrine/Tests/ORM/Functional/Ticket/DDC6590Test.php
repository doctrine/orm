<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;

class DDC6590Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(DDC6590Source::class),
                $this->_em->getClassMetadata(DDC6590Lead::class),
            ]
        );
    }

    public function testIssue()
    {
        $s1 = new DDC6590Source();
        $s1->name = 's1';
        $s2 = clone $s1;
        $s2->name = 's2';
        $lead = new DDC6590Lead();
        $lead->name = 'lead';
        $lead->sources = new ArrayCollection([$s1]);

        $this->_em->persist($s1);
        $this->_em->persist($s2);
        $this->_em->persist($lead);
        $this->_em->flush();

        $this->_em->getEventManager()->addEventSubscriber(new DDC6590Subscriber());
        $lead->sources = new ArrayCollection([$s1, $s2]);
        $this->_em->flush();

        $this->_em->refresh($lead);
        $this->assertCount(2, $lead->sources);
    }
}

/**
 * @Entity
 */
class DDC6590Source
{
    /**
     *  @Id()
     *  @Column(name="id", type="integer")
     *  @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(name="name", type="string", length=32)
     */
    public $name;
}

/**
 * @Entity
 */
class DDC6590Lead
{
    /**
     *  @Id()
     *  @Column(name="id", type="integer")
     *  @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column (name="name", type="string", length=32)
     */
    public $name;

    /**
     * @ManyToMany(targetEntity="DDC6590Source")
     * @JoinTable(name="lead_source_6590",
     *      joinColumns={@JoinColumn(name="lead_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="source_id", referencedColumnName="id")}
     * )
     */
    public $sources;

    public function __construct() {
        $this->sources = new ArrayCollection();
    }
}

class DDC6590Subscriber implements EventSubscriber
{
    private $isCalled = false;

    public function getSubscribedEvents() {
        return [\Doctrine\ORM\Events::postFlush];
    }

    public function postFlush(PostFlushEventArgs $args) {
        if ($this->isCalled === false) {
            $this->isCalled = true;
            $em = $args->getEntityManager();
            $em->flush();
        }
    }
}
