<?php

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity @ORM\Table(name="company_events")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="event_type", type="string")
 * @ORM\DiscriminatorMap({"auction"="CompanyAuction", "raffle"="CompanyRaffle"})
 */
abstract class CompanyEvent {
   /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="CompanyOrganization", inversedBy="events", cascade={"persist"})
     * @ORM\JoinColumn(name="org_id", referencedColumnName="id")
     */
     private $organization;

     public function getId() {
         return $this->id;
     }

     public function getOrganization() {
         return $this->organization;
     }

     public function setOrganization(CompanyOrganization $org) {
         $this->organization = $org;
     }

}