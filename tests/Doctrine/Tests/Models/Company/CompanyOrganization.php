<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_organizations")
 */
class CompanyOrganization {
   /**
    * @ORM\Id @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
   private $id;

    /**
     * @ORM\OneToMany(targetEntity="CompanyEvent", mappedBy="organization", cascade={"persist"}, fetch="EXTRA_LAZY")
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
     * @ORM\OneToOne(targetEntity="CompanyEvent", cascade={"persist"})
     * @ORM\JoinColumn(name="main_event_id", referencedColumnName="id", nullable=true)
     */
    private $mainevent;

    public function getMainEvent() {
        return $this->mainevent;
    }

    public function setMainEvent($event) {
        $this->mainevent = $event;
    }
}
