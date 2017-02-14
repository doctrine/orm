<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="quote-address")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"simple" = Address::class, "full" = FullAddress::class})
 */
class Address
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="address-id")
     */
    public $id;

    /**
     * @ORM\Column(name="address-zip")
     */
    public $zip;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="address")
     * @ORM\JoinColumn(name="user-id", referencedColumnName="user-id")
     */
    public $user;

    public function setUser(User $user) {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getZip()
    {
        return $this->zip;
    }

    public function getUser()
    {
        return $this->user;
    }
}
