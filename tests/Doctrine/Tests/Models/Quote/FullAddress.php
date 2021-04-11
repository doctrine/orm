<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

/**
 * @Entity
 */
class FullAddress extends Address
{
    /**
     * @var City
     * @OneToOne(targetEntity=City::class, cascade={"persist"})
     * @JoinColumn(name="`city-id`", referencedColumnName="`city-id`")
     */
    public $city;
}
