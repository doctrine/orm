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
     * @OneToOne(targetEntity="Address", mappedBy="user", cascade={"persist"})
     */
    public $address;

    /**
     * @ManyToMany(targetEntity="Group", inversedBy="users", cascade={"persist"})
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

    public function __construct()
    {
        $this->phones = new ArrayCollection;
        $this->groups = new ArrayCollection;
    }

}
