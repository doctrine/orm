<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CompanyCarContract extends CompanyContract
{
    #[ORM\ManyToOne(targetEntity: CompanyCar::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CompanyCar $companyCar;

    public function calculatePrice(): int
    {
        return 0;
    }

    public function setCompanyCar(CompanyCar $companyCar): void
    {
        $this->companyCar = $companyCar;
    }
}
