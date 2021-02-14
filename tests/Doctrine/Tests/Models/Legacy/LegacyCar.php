<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

/**
 * @Entity
 * @Table(name="legacy_cars")
 */
class LegacyCar
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(name="iCarId", type="integer", nullable=false)
     */
    public $_id;
    /** @ManyToMany(targetEntity="LegacyUser", mappedBy="_cars") */
    public $_users;

    /**
     * @var string
     * @Column(name="sDescription", type="string", length=255, unique=true)
     */
    public $_description;

    public function getDescription()
    {
        return $this->_description;
    }

    public function addUser(LegacyUser $user): void
    {
        $this->_users[] = $user;
    }

    public function getUsers()
    {
        return $this->_users;
    }
}
