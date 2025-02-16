<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11386;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class GH11386EntityCart
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    private int|null $id = null;

    #[Column]
    private int|null $amount = null;

    #[OneToOne(inversedBy: 'cart', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private GH11386EntityCustomer|null $customer = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getAmount(): int|null
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCustomer(): GH11386EntityCustomer|null
    {
        return $this->customer;
    }

    public function setCustomer(GH11386EntityCustomer|null $customer): self
    {
        $this->customer = $customer;

        return $this;
    }
}
