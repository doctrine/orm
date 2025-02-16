<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Order_Master')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $company;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    public User $user;

    public function __construct(User $user)
    {
        $this->user    = $user;
        $this->company = $user->company;
    }
}
