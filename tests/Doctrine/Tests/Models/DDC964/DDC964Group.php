<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC964Group
{
    /**
     * @ORM\GeneratedValue
     * @ORM\Id @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column */
    private $name;

    /** @ORM\ManyToMany(targetEntity=DDC964User::class, mappedBy="groups") */
    private $users;

    public function __construct($name = null)
    {
        $this->name  = $name;
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
