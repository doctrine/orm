<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFlexUltraContractListener;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"CompanyContractListener","CompanyFlexUltraContractListener"})
 */
class CompanyFlexUltraContract extends CompanyFlexContract
{
    /**
     * @ORM\Column(type="integer")
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
