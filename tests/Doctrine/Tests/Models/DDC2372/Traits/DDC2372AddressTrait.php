<?php

namespace Doctrine\Tests\Models\DDC2372\Traits;

use Doctrine\ORM\Annotation as ORM;

trait DDC2372AddressTrait
{
    /**
     * @ORM\OneToOne(targetEntity="Doctrine\Tests\Models\DDC2372\DDC2372Address", inversedBy="user")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
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