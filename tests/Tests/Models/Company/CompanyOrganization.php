<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'company_organizations')]
#[Entity]
class CompanyOrganization
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /** @phpstan-var Collection<int, CompanyEvent> */
    #[OneToMany(targetEntity: 'CompanyEvent', mappedBy: 'organization', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    public $events;

    public function getId(): int
    {
        return $this->id;
    }

    /** @phpstan-return Collection<int, CompanyEvent> */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(CompanyEvent $event): void
    {
        $this->events[] = $event;
        $event->setOrganization($this);
    }

    #[OneToOne(targetEntity: 'CompanyEvent', cascade: ['persist'])]
    #[JoinColumn(name: 'main_event_id', referencedColumnName: 'id', nullable: true)]
    private CompanyEvent|null $mainevent = null;

    public function getMainEvent(): CompanyEvent|null
    {
        return $this->mainevent;
    }

    public function setMainEvent(CompanyEvent $event): void
    {
        $this->mainevent = $event;
    }
}
