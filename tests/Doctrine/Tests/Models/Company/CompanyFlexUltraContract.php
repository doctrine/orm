<?php

namespace Doctrine\Tests\Models\Company;

/**
 * @Entity
 */
class CompanyFlexUltraContract extends CompanyFlexContract
{
    /**
     * @column(type="integer")
     * @var int
     */
    private $maxPrice = 0;

    public function calculatePrice()
    {
        return max($this->maxPrice, parent::calculatePrice());
    }

    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    public function setMaxPrice($maxPrice)
    {
        $this->maxPrice = $maxPrice;
    }
}