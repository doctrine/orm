<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

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
     * @Id
     * @Column(type="integer")
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

    public function getDays(): int|string
    {
        return $this->days;
    }

    public function setDays(int|string $days): void
    {
        $this->days = $days;
    }
}
