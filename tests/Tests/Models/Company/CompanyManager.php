<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'company_managers')]
#[Entity]
class CompanyManager extends CompanyEmployee
{
    #[Column(type: 'string', length: 250)]
    private string|null $title = null;

    #[OneToOne(targetEntity: 'CompanyCar', cascade: ['persist'])]
    #[JoinColumn(name: 'car_id', referencedColumnName: 'id')]
    private CompanyCar|null $car = null;

    /** @phpstan-var Collection<int, CompanyFlexContract> */
    #[ManyToMany(targetEntity: 'CompanyFlexContract', mappedBy: 'managers', fetch: 'EXTRA_LAZY')]
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
