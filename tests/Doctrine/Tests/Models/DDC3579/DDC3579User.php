<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\MappedSuperClass
 */
class DDC3579User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="user_id", length=150)
     */
    protected $id;

    /** @ORM\Column(name="user_name", nullable=true, unique=false, length=250) */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity=DDC3579Group::class)
     *
     * @var ArrayCollection
     */
    protected $groups;

    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->name   = $name;
        $this->groups = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function addGroup(DDC3579Group $group)
    {
        $this->groups->add($group);
        $group->addUser($this);
    }

    /**
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
