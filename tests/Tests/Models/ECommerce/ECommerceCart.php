<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * ECommerceCart
 * Represents a typical cart of a shopping application.
 */
#[Table(name: 'ecommerce_carts')]
#[Entity]
class ECommerceCart
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue]
    private int $id;

    #[Column(length: 50, nullable: true)]
    private string|null $payment = null;

    #[OneToOne(targetEntity: 'ECommerceCustomer', inversedBy: 'cart')]
    #[JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
    private ECommerceCustomer|null $customer = null;

    /** @phpstan-var Collection<int, ECommerceProduct> */
    #[JoinTable(name: 'ecommerce_carts_products')]
    #[JoinColumn(name: 'cart_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'product_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'ECommerceProduct', cascade: ['persist'])]
    private $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPayment(): string
    {
        return $this->payment;
    }

    public function setPayment(string $payment): void
    {
        $this->payment = $payment;
    }

    public function setCustomer(ECommerceCustomer $customer): void
    {
        if ($this->customer !== $customer) {
            $this->customer = $customer;
            $customer->setCart($this);
        }
    }

    public function removeCustomer(): void
    {
        if ($this->customer !== null) {
            $customer       = $this->customer;
            $this->customer = null;
            $customer->removeCart();
        }
    }

    public function getCustomer(): ECommerceCustomer|null
    {
        return $this->customer;
    }

    /** @phpstan-return Collection<int, ECommerceProduct> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(ECommerceProduct $product): void
    {
        $this->products[] = $product;
    }

    public function removeProduct(ECommerceProduct $product): bool
    {
        return $this->products->removeElement($product);
    }
}
