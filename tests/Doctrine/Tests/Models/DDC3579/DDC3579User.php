<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @MappedSuperclass
 */
class DDC3579User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="user_id", length=150)
     */
    protected $id;

    /** @Column(name="user_name", nullable=true, unique=false, length=250) */
    protected $name;

    /**
     * @var ArrayCollection
     * @ManyToMany(targetEntity="DDC3579Group")
     */
    protected $groups;

    public function __construct(?string $name = null)
    {
        $this->name   = $name;
        $this->groups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addGroup(DDC3579Group $group): void
    {
        $this->groups->add($group);
        $group->addUser($this);
    }

    public function getGroups(): ArrayCollection
    {
        return $this->groups;
    }

    public static function loadMetadata($metadata): void
    {
        $metadata->isMappedSuperclass = true;

        $metadata->mapField(
            [
                'id'         => true,
                'fieldName'  => 'id',
                'type'       => 'integer',
                'columnName' => 'user_id',
                'length'     => 150,
            ]
        );

        $metadata->mapField(
            [
                'fieldName' => 'name',
                'type'      => 'string',
                'columnName' => 'user_name',
                'nullable'  => true,
                'unique'    => false,
                'length'    => 250,
            ]
        );

        $metadata->mapManyToMany(
            [
                'fieldName'      => 'groups',
                'targetEntity'   => 'DDC3579Group',
            ]
        );

        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
    }
}
