<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="`quote-user`")
 */
class User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="`user-id`")
     */
    public $id;

    /**
     * @Column(type="string", name="`user-name`")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="Phone", mappedBy="user", cascade={"persist"})
     */
    public $phones;

    /**
     * @JoinColumn(name="`address-id`", referencedColumnName="`address-id`")
     * @OneToOne(targetEntity="Address", mappedBy="user", cascade={"persist"}, fetch="EAGER")
     */
    public $address;

    /**
     * @ManyToMany(targetEntity="Group", inversedBy="users", cascade={"all"})
     * @JoinTable(name="`quote-users-groups`",
     *      joinColumns={
     *          @JoinColumn(
     *              name="`user-id`",
     *              referencedColumnName="`user-id`"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @JoinColumn(
     *              name="`group-id`",
     *              referencedColumnName="`group-id`"
     *          )
     *      }
     * )
     */
    public $groups;

    /**
     * @ManyToMany(targetEntity="Group", inversedBy="users", cascade={"all"}, fetch="EXTRA_LAZY")
     * @JoinTable(name="`quote-extra-lazy-users-groups`",
     *      joinColumns={
     *          @JoinColumn(
     *              name="`user-id`",
     *              referencedColumnName="`user-id`"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @JoinColumn(
     *              name="`group-id`",
     *              referencedColumnName="`group-id`"
     *          )
     *      }
     * )
     */
    public $extraLazyGroups;

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
