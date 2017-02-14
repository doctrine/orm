<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="quote-user")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="user-id")
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="user-name")
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="Phone", mappedBy="user", cascade={"persist"})
     */
    public $phones;

    /**
     * @ORM\JoinColumn(name="address-id", referencedColumnName="address-id")
     * @ORM\OneToOne(targetEntity="Address", mappedBy="user", cascade={"persist"}, fetch="EAGER")
     */
    public $address;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users", cascade={"all"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="quote-users-groups",
     *      joinColumns={
     *          @ORM\JoinColumn(
     *              name="user-id",
     *              referencedColumnName="user-id"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(
     *              name="group-id",
     *              referencedColumnName="group-id"
     *          )
     *      }
     * )
     */
    public $groups;

    public function __construct()
    {
        $this->phones = new ArrayCollection;
        $this->groups = new ArrayCollection;
    }


    public function getPhones()
    {
        return $this->phones;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setAddress(Address $address) {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
