<?php

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC3579Group
{
    /**
     * @ORM\GeneratedValue
     * @ORM\Id @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="DDC3579Admin", mappedBy="groups")
     */
    private $admins;

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
     * @param DDC3579Admin $admin
     */
    public function addAdmin(DDC3579Admin $admin)
    {
        $this->admins[] = $admin;
    }

    /**
     * @return ArrayCollection
     */
    public function getAdmins()
    {
        return $this->admins;
    }

}

