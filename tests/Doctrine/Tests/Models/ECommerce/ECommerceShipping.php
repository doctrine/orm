<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Annotation as ORM;

/**
 * ECommerceShipping
 * Represents a shipping method.
 *
 * @ORM\Entity
 * @ORM\Table(name="ecommerce_shippings")
 */
class ECommerceShipping
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /** @ORM\Column(type="integer") */
    private $days;

    public function getId()
    {
        return $this->id;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
    }
}
