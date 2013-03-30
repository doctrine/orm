<?php

namespace Entities;

use Entities\Traits\AddressTrait;

/** @Entity @Table(name="traited_users") */
class TraitedUser
{
    use AddressTrait;

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @Column(type="string", length=50) */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}