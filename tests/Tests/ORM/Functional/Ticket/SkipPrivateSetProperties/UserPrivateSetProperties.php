<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SkipPrivateSetProperties;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Test_Class_With_Private_Set_Properties_User')]
class UserPrivateSetProperties
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    private(set) string $company;

    public function __construct(string $company)
    {
        $this->company = $company;
    }
}
