<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\DDC11871;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'DDC11871_Order')]
class DDC11871Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column]
    private(set) string $company;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: DDC11871User::class, fetch: 'LAZY')]
        private(set) DDC11871User $user,
    ) {
        $this->company = $user->company;
    }
}
