<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'company_events')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'event_type', type: 'string', length: 255)]
#[DiscriminatorMap(['auction' => 'CompanyAuction', 'raffle' => 'CompanyRaffle'])]
abstract class CompanyEvent
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[ManyToOne(targetEntity: 'CompanyOrganization', inversedBy: 'events', cascade: ['persist'])]
    #[JoinColumn(name: 'org_id', referencedColumnName: 'id')]
    private CompanyOrganization|null $organization = null;

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
