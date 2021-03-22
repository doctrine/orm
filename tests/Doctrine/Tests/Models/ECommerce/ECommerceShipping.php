<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceShipping
 * Represents a shipping method.
 *
 * @Entity
 * @Table(name="ecommerce_shippings")
 */
class ECommerceShipping
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var int|string
     * @Column(type="integer")
     */
    private $days;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|string
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param int|string $days
     */
    public function setDays($days): void
    {
        $this->days = $days;
    }
}
