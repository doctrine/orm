<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\DDC11871;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'DDC11871_User')]
class DDC11871User
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
