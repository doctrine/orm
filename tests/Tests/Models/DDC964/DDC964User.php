<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;

#[MappedSuperclass]
class DDC964User
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer', name: 'user_id', length: 150)]
    protected $id;

    /** @phpstan-var Collection<int, DDC964Group> */
    #[ManyToMany(targetEntity: DDC964Group::class, inversedBy: 'users', cascade: ['persist', 'detach'])]
    #[JoinTable(name: 'ddc964_users_groups')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    protected $groups;

    /** @var DDC964Address */
    #[ManyToOne(targetEntity: DDC964Address::class, cascade: ['persist'])]
    #[JoinColumn(name: 'address_id', referencedColumnName: 'id')]
    protected $address;

    public function __construct(
        #[Column(name: 'user_name', nullable: true, unique: false, length: 250)]
        protected string|null $name = null,
    ) {
        $this->groups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addGroup(DDC964Group $group): void
    {
        $this->groups->add($group);
        $group->addUser($this);
    }

    /** @phpstan-return Collection<int, DDC964Group> */
    public function getGroups(): ArrayCollection
    {
        return $this->groups;
    }

    public function getAddress(): DDC964Address
    {
        return $this->address;
    }

    public function setAddress(DDC964Address $address): void
    {
        $this->address = $address;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->isMappedSuperclass = true;

        $metadata->mapField(
            [
                'id'         => true,
                'fieldName'  => 'id',
                'type'       => 'integer',
                'columnName' => 'user_id',
                'length'     => 150,
            ],
        );
        $metadata->mapField(
            [
                'fieldName' => 'name',
                'type'      => 'string',
                'columnName' => 'user_name',
                'nullable'  => true,
                'unique'    => false,
                'length'    => 250,
            ],
        );

        $metadata->mapManyToOne(
            [
                'fieldName'      => 'address',
                'targetEntity'   => 'DDC964Address',
                'cascade'        => ['persist'],
                'joinColumns'    => [['name' => 'address_id', 'referencedColumnName' => 'id']],
            ],
        );

        $metadata->mapManyToMany(
            [
                'fieldName'      => 'groups',
                'targetEntity'   => 'DDC964Group',
                'inversedBy'     => 'users',
                'cascade'        => ['persist','detach'],
                'joinTable'      => [
                    'name'          => 'ddc964_users_groups',
                    'joinColumns'   => [
                        [
                            'name' => 'user_id',
                            'referencedColumnName' => 'id',
                        ],
                    ],
                    'inverseJoinColumns' => [
                        [
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                        ],
                    ],
                ],
            ],
        );

        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
    }
}
