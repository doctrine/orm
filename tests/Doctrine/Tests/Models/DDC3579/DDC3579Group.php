<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;

/** @Entity */
#[Entity]
class DDC3579Group
{
    /**
     * @GeneratedValue
     * @Id
     * @Column(type="integer")
     */
    #[Id, GeneratedValue, Column(type: 'integer')]
    private int $id;

    /**
     * @psalm-var Collection<int, DDC3579Admin>
     * @ManyToMany(targetEntity="DDC3579Admin", mappedBy="groups")
     */
    #[ManyToMany(targetEntity: DDC3579Admin::class, mappedBy: 'groups')]
    private $admins;

    public function __construct(
        /** @Column */
        #[Column] private string|null $name = null,
    ) {
        $this->admins = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function addAdmin(DDC3579Admin $admin): void
    {
        $this->admins[] = $admin;
    }

    /** @psalm-return Collection<int, DDC3579Admin> */
    public function getAdmins(): Collection
    {
        return $this->admins;
    }
}
