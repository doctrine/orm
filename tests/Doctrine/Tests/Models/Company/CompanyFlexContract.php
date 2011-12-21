<?php
namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 */
class CompanyFlexContract extends CompanyContract
{
    /**
     * @column(type="integer")
     * @var int
     */
    private $hoursWorked = 0;

    /**
     * @column(type="integer")
     * @var int
     */
    private $pricePerHour = 0;

    /**
     * @ManyToMany(targetEntity="CompanyManager", inversedBy="managedContracts", fetch="EXTRA_LAZY")
     * @JoinTable(name="company_contract_managers",
     *    joinColumns={@JoinColumn(name="contract_id", referencedColumnName="id", onDelete="CASCADE")},
     *    inverseJoinColumns={@JoinColumn(name="employee_id", referencedColumnName="id")}
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
