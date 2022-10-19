<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;

/** @Entity */
class CompanyCarContract extends CompanyContract
{
    /**
     * @ORM\ManyToOne(targetEntity="CompanyCar")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     *
     * @var CompanyCar
     */
    private $companyCar;

    public function calculatePrice(): int
    {
        return 0;
    }

    public function setCompanyCar(CompanyCar $companyCar): void
    {
        $this->companyCar = $companyCar;
    }
}
