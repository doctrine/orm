<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\Collection;

/** @Entity @Table(name="company_organizations") */
class CompanyOrganization
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @psalm-var Collection<int, CompanyEvent>
     * @OneToMany(targetEntity="CompanyEvent", mappedBy="organization", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    public $events;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @psalm-return Collection<int, CompanyEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(CompanyEvent $event): void
    {
        $this->events[] = $event;
        $event->setOrganization($this);
    }

    /**
     * @var CompanyEvent|null
     * @OneToOne(targetEntity="CompanyEvent", cascade={"persist"})
     * @JoinColumn(name="main_event_id", referencedColumnName="id", nullable=true)
     */
    private $mainevent;

    public function getMainEvent(): ?CompanyEvent
    {
        return $this->mainevent;
    }

    public function setMainEvent(CompanyEvent $event): void
    {
        $this->mainevent = $event;
    }
}
