<?php

declare(strict_types=1);

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

    /** @Column */
    private $name;

    /**
     * @ArrayCollection
     * @ManyToMany(targetEntity="DDC964User", mappedBy="groups")
     */
    private $users;

    public function __construct($name = null)
    {
        $this->name  = $name;
        $this->users = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addUser(DDC964User $user): void
    {
        $this->users[] = $user;
    }

    public function getUsers(): ArrayCollection
    {
        return $this->users;
    }
}
