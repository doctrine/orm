<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'company_employees')]
#[Entity]
class CompanyEmployee extends CompanyPerson
{
    #[Column(type: 'integer')]
    private int|null $salary = null;

    #[Column(type: 'string', length: 255)]
    private string|null $department = null;

    #[Column(type: 'datetime', nullable: true)]
    private DateTime|null $startDate = null;

    /** @phpstan-var Collection<int, CompanyContract> */
    #[ManyToMany(targetEntity: 'CompanyContract', mappedBy: 'engineers', fetch: 'EXTRA_LAZY')]
    public $contracts;

    /** @phpstan-var Collection<int, CompanyFlexUltraContract> */
    #[OneToMany(targetEntity: 'CompanyFlexUltraContract', mappedBy: 'salesPerson', fetch: 'EXTRA_LAZY')]
    public $soldContracts;

    public function getSalary(): int
    {
        return $this->salary;
    }

    public function setSalary(int $salary): void
    {
        $this->salary = $salary;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function setDepartment(string $dep): void
    {
        $this->department = $dep;
    }

    public function getStartDate(): DateTime|null
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $date): void
    {
        $this->startDate = $date;
    }
}
