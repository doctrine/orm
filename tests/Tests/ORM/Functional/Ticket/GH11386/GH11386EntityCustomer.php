<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11386;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class GH11386EntityCustomer
{
    #[Id]
    #[GeneratedValue]
    #[Column]
    private int|null $id = null;

    #[Column]
    private string|null $name = null;

    #[Column(type: 'smallint', nullable: true, enumType: GH11386EnumType::class, options: ['unsigned' => true])]
    private GH11386EnumType|null $type = null;

    #[OneToOne(mappedBy: 'customer')]
    private GH11386EntityCart|null $cart = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): GH11386EnumType|null
    {
        return $this->type;
    }

    public function setType(GH11386EnumType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCart(): GH11386EntityCart|null
    {
        return $this->cart;
    }

    public function setCart(GH11386EntityCart|null $cart): self
    {
        // unset the owning side of the relation if necessary
        if ($cart === null && $this->cart !== null) {
            $this->cart->setCustomer(null);
        }

        // set the owning side of the relation if necessary
        if ($cart !== null && $cart->getCustomer() !== $this) {
            $cart->setCustomer($this);
        }

        $this->cart = $cart;

        return $this;
    }
}
