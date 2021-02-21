<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2372\Traits;

use Doctrine\Tests\Models\DDC2372\DDC2372Address;

trait DDC2372AddressTrait
{
    /**
     * @var DDC2372Address
     * @OneToOne(targetEntity="Doctrine\Tests\Models\DDC2372\DDC2372Address", inversedBy="user")
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
