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
     * @OneToMany(targetEntity="CompanyEvent", mappedBy="organization", cascade={"persist"})
     */
    private $events;
    
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
}