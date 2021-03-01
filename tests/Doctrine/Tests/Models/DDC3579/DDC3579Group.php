<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC3579Group
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
     * @ManyToMany(targetEntity="DDC3579Admin", mappedBy="groups")
     */
    private $admins;

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

    public function addAdmin(DDC3579Admin $admin): void
    {
        $this->admins[] = $admin;
    }

    public function getAdmins(): ArrayCollection
    {
        return $this->admins;
    }
}
