<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * ECommerceCustomer
 * Represents a registered user of a shopping application.
 */
#[Table(name: 'ecommerce_customers')]
#[Entity]
class ECommerceCustomer
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', length: 50)]
    private string|null $name = null;

    #[OneToOne(targetEntity: 'ECommerceCart', mappedBy: 'customer', cascade: ['persist'])]
    private ECommerceCart|null $cart = null;

    /**
     * Example of a one-one self referential association. A mentor can follow
     * only one customer at the time, while a customer can choose only one
     * mentor. Not properly appropriate but it works.
     */
    #[OneToOne(targetEntity: 'ECommerceCustomer', cascade: ['persist'], fetch: 'EAGER')]
    #[JoinColumn(name: 'mentor_id', referencedColumnName: 'id')]
    private ECommerceCustomer|null $mentor = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCart(ECommerceCart $cart): void
    {
        if ($this->cart !== $cart) {
            $this->cart = $cart;
            $cart->setCustomer($this);
        }
    }

    /** Does not properly maintain the bidirectional association! */
    public function brokenSetCart(ECommerceCart $cart): void
    {
        $this->cart = $cart;
    }

    public function getCart(): ECommerceCart|null
    {
        return $this->cart;
    }

    public function removeCart(): void
    {
        if ($this->cart !== null) {
            $cart       = $this->cart;
            $this->cart = null;
            $cart->removeCustomer();
        }
    }

    public function setMentor(ECommerceCustomer $mentor): void
    {
        $this->mentor = $mentor;
    }

    public function removeMentor(): void
    {
        $this->mentor = null;
    }

    public function getMentor(): ECommerceCustomer|null
    {
        return $this->mentor;
    }
}
