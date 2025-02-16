<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: '`quote-user`')]
#[Entity]
class User
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer', name: '`user-id`')]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 255, name: '`user-name`')]
    public $name;

    /** @phpstan-var Collection<int, Phone> */
    #[OneToMany(targetEntity: 'Phone', mappedBy: 'user', cascade: ['persist'])]
    public $phones;

    /** @var Address */
    #[OneToOne(targetEntity: 'Address', mappedBy: 'user', cascade: ['persist'], fetch: 'EAGER')]
    public $address;

    /** @phpstan-var Collection<int, Group> */
    #[JoinTable(name: '`quote-users-groups`')]
    #[JoinColumn(name: '`user-id`', referencedColumnName: '`user-id`')]
    #[InverseJoinColumn(name: '`group-id`', referencedColumnName: '`group-id`')]
    #[ManyToMany(targetEntity: 'Group', inversedBy: 'users', cascade: ['all'], fetch: 'EXTRA_LAZY')]
    public $groups;

    public function __construct()
    {
        $this->phones = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    /** @phpstan-return Collection<int, Phone> */
    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function getAddress(): Address|null
    {
        return $this->address;
    }

    /** @phpstan-return Collection<int, Group> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function setAddress(Address $address): void
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
