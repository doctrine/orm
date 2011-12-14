<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC964Group
{

    /**
     * @GeneratedValue
     * @Id @Column(type="integer")
     */
    private $id;

    /**
     * @Column
     */
    private $name;

    /**
     * @ArrayCollection
     * 
     * @ManyToMany(targetEntity="DDC964User", mappedBy="groups")
     */
    private $users;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->users = new ArrayCollection();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param DDC964User $user
     */
    public function addUser(DDC964User $user)
    {
        $this->users[] = $user;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

}

