<?php

namespace Entities\Traits;

trait AddressTrait
{
    /**
     * @OneToOne(targetEntity="Entities\Address", inversedBy="user")
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(Address $address)
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}