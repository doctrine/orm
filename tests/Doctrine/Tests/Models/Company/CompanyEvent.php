<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity @Table(name="company_events")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="event_type", type="string")
 * @DiscriminatorMap({"auction" = "CompanyAuction", "raffle" = "CompanyRaffle"})
 */
class CompanyEvent {
   /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="CompanyOrganization",cascade={"persist"})
     * @JoinColumn(name="org_id", referencedColumnName="id")
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