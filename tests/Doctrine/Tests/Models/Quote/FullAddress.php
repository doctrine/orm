<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class FullAddress extends Address
{
    /**
     * @ORM\OneToOne(targetEntity=City::class, cascade={"persist"})
     * @ORM\JoinColumn(name="city-id", referencedColumnName="city-id")
     *
     * @var City
     */
    public $city;
}
