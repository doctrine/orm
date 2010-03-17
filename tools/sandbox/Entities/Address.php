<?php

namespace Entities;

/** @Entity @Table(name="addresses") */
class Address
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @Column(type="string", length=255) */
    private $street;
    /** @OneToOne(targetEntity="User", mappedBy="address") */
    private $user;

    public function getId()
    {
        return $this->id;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setStreet($street)
    {
        $this->street = $street;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}