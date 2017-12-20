<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity @Table(name="company_events")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="event_type", type="string")
 * @DiscriminatorMap({"auction"="CompanyAuction", "raffle"="CompanyRaffle"})
 */
abstract class CompanyEvent
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var CompanyOrganization
     * @ManyToOne(targetEntity="CompanyOrganization", inversedBy="events", cascade={"persist"})
     * @JoinColumn(name="org_id", referencedColumnName="id")
     */
     private $organization;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrganization(): CompanyOrganization
    {
        return $this->organization;
    }

    public function setOrganization(CompanyOrganization $org): void
    {
        $this->organization = $org;
    }
}
