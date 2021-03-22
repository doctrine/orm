<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="company_managers")
 */
class CompanyManager extends CompanyEmployee
{
    /**
     * @var string
     * @Column(type="string", length=250)
     */
    private $title;

    /**
     * @var CompanyCar
     * @OneToOne(targetEntity="CompanyCar", cascade={"persist"})
     * @JoinColumn(name="car_id", referencedColumnName="id")
     */
    private $car;

    /**
     * @psalm-var Collection<int, CompanyFlexContract>
     * @ManyToMany(targetEntity="CompanyFlexContract", mappedBy="managers", fetch="EXTRA_LAZY")
     */
    public $managedContracts;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getCar(): CompanyCar
    {
        return $this->car;
    }

    public function setCar(CompanyCar $car): void
    {
        $this->car = $car;
    }
}
