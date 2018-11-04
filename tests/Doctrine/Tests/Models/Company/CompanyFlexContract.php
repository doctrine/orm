<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class CompanyFlexContract extends CompanyContract
{
    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $hoursWorked = 0;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $pricePerHour = 0;

    /**
     * @ORM\ManyToMany(targetEntity=CompanyManager::class, inversedBy="managedContracts", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="company_contract_managers",
     *    joinColumns={@ORM\JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE")},
     *    inverseJoinColumns={@ORM\JoinColumn(name="employee_id", referencedColumnName="id")}
     * )
     */
    public $managers;

    public function calculatePrice()
    {
        return $this->hoursWorked * $this->pricePerHour;
    }

    public function getHoursWorked()
    {
        return $this->hoursWorked;
    }

    public function setHoursWorked($hoursWorked)
    {
        $this->hoursWorked = $hoursWorked;
    }

    public function getPricePerHour()
    {
        return $this->pricePerHour;
    }

    public function setPricePerHour($pricePerHour)
    {
        $this->pricePerHour = $pricePerHour;
    }
    public function getManagers()
    {
        return $this->managers;
    }

    public function addManager(CompanyManager $manager)
    {
        $this->managers[] = $manager;
    }

    public function removeManager(CompanyManager $manager)
    {
        $this->managers->removeElement($manager);
    }
}
