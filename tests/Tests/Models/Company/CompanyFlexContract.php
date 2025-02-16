<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;

#[ORM\Entity]
class CompanyFlexContract extends CompanyContract
{
    #[Column(type: 'integer')]
    private int $hoursWorked = 0;

    #[Column(type: 'integer')]
    private int $pricePerHour = 0;

    /** @phpstan-var Collection<int, CompanyManager> */
    #[JoinTable(name: 'company_contract_managers')]
    #[JoinColumn(name: 'contract_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[InverseJoinColumn(name: 'employee_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'CompanyManager', inversedBy: 'managedContracts', fetch: 'EXTRA_LAZY')]
    public $managers;

    public function calculatePrice(): int
    {
        return $this->hoursWorked * $this->pricePerHour;
    }

    public function getHoursWorked(): int
    {
        return $this->hoursWorked;
    }

    public function setHoursWorked(int $hoursWorked): void
    {
        $this->hoursWorked = $hoursWorked;
    }

    public function getPricePerHour(): int
    {
        return $this->pricePerHour;
    }

    public function setPricePerHour(int $pricePerHour): void
    {
        $this->pricePerHour = $pricePerHour;
    }

    /** @phpstan-return Collection<int, CompanyManager> */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(CompanyManager $manager): void
    {
        $this->managers[] = $manager;
    }

    public function removeManager(CompanyManager $manager): void
    {
        $this->managers->removeElement($manager);
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'type'      => 'integer',
                'name'      => 'hoursWorked',
                'fieldName' => 'hoursWorked',
            ],
        );

        $metadata->mapField(
            [
                'type'      => 'integer',
                'name'      => 'pricePerHour',
                'fieldName' => 'pricePerHour',
            ],
        );
    }
}
