<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * ECommerceProduct2
 * Resets the id when being cloned.
 *
 * @Entity
 * @Table(name="ecommerce_products",indexes={@Index(name="name_idx", columns={"name"})})
 */
class ECommerceProduct2
{
    /**
     * @var int|null
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column(type="string", length=50, nullable=true)
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __clone()
    {
        $this->id   = null;
        $this->name = 'Clone of ' . $this->name;
    }
}
