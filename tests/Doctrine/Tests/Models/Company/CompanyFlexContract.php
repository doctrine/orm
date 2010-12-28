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
}