<?php

namespace Doctrine\Tests\Models\Company;

/** @Entity @Table(name="company_organizations") */
class CompanyOrganization {
   /**
    * @Id @Column(type="integer")
    * @GeneratedValue(strategy="AUTO")
    */
   private $id;

    /**
     * @OneToMany(targetEntity="CompanyEvent", mappedBy="organization", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $events;

    public function getId() {
        return $this->id;
    }

    public function getEvents() {
        return $this->events;
    }

    public function addEvent(CompanyEvent $event) {
        $this->events[] = $event;
        $event->setOrganization($this);
    }

    /**
     * @OneToOne(targetEntity="CompanyEvent", cascade={"persist"})
     * @JoinColumn(name="main_event_id", referencedColumnName="id", nullable=true)
     */
    private $mainevent;

    public function getMainEvent() {
        return $this->mainevent;
    }

    public function setMainEvent($event) {
        $this->mainevent = $event;
    }
}
