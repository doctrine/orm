<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * @MappedSuperclass
 */
class DDC964User
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", name="user_id")
     */
    protected $id;

    /**
     * @Column(name="user_name", nullable=true, unique=false, length=250)
     */
    protected $name;

    /**
     * @var ArrayCollection
     *
     * @ManyToMany(targetEntity="DDC964Group", inversedBy="users", cascade={"persist", "merge", "detach"})
     * @JoinTable(name="ddc964_users_groups",
     *  joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *  inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @var DDC964Address
     *
     * @ManyToOne(targetEntity="DDC964Address", cascade={"persist", "merge"})
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    protected $address;

    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->name     = $name;
        $this->groups   = new ArrayCollection;
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

    /**
     * @param DDC964Group $group
     */
    public function addGroup(DDC964Group $group)
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

    /**
     * @return DDC964Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param DDC964Address $address
     */
    public function setAddress(DDC964Address $address)
    {
        $this->address = $address;
    }

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');
        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('user_id');
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('name');
        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(250);
        $fieldMetadata->setColumnName('user_name');
        $fieldMetadata->setNullable(true);
        $fieldMetadata->setUnique(false);

        $metadata->addProperty($fieldMetadata);

        $joinColumns = [];

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('address_id');
        $joinColumn->setReferencedColumnName('id');

        $joinColumns[] = $joinColumn;

        $metadata->mapManyToOne(
            [
               'fieldName'      => 'address',
               'targetEntity'   => 'DDC964Address',
               'cascade'        => ['persist','merge'],
               'joinColumns'    => $joinColumns,
            ]
        );

        $joinTable = new Mapping\JoinTableMetadata();
        $joinTable->setName('ddc964_users_groups');

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('user_id');
        $joinColumn->setReferencedColumnName('id');

        $joinTable->addJoinColumn($joinColumn);

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');

        $joinTable->addInverseJoinColumn($joinColumn);

        $metadata->mapManyToMany(
            [
               'fieldName'    => 'groups',
               'targetEntity' => 'DDC964Group',
               'inversedBy'   => 'users',
               'cascade'      => ['persist','merge','detach'],
               'joinTable'    => $joinTable,
            ]
        );

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO);
    }
}
