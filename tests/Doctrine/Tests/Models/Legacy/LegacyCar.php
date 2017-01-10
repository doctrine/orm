<?php

namespace Doctrine\Tests\Models\Legacy;

/**
 * @Entity
 * @Table(name="legacy_cars")
 */
class LegacyCar
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(name="iCarId", type="integer", nullable=false)
     */
    public $id;
    /**
     * @ManyToMany(targetEntity="LegacyUser", mappedBy="cars")
     */
    public $users;

    /**
     * @Column(name="sDescription", type="string", length=255, unique=true)
     */
    public $description;

    function getDescription()
    {
        return $this->description;
    }

    public function addUser(LegacyUser $user) {
        $this->users[] = $user;
    }

    public function getUsers() {
        return $this->users;
    }
}
