<?php

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="legacy_cars")
 */
class LegacyCar
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="iCarId", type="integer", nullable=false)
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="LegacyUser", mappedBy="cars")
     */
    public $users;

    /**
     * @ORM\Column(name="sDescription", type="string", length=255, unique=true)
     */
    public $description;

    public function getDescription()
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
