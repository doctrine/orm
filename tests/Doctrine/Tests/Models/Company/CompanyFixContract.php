<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class CompanyFixContract extends CompanyContract
{
    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $fixPrice = 0;

    public function calculatePrice()
    {
        return $this->fixPrice;
    }

    public function getFixPrice()
    {
        return $this->fixPrice;
    }

    public function setFixPrice($fixPrice)
    {
        $this->fixPrice = $fixPrice;
    }
}
