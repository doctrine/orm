<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\MappedSuperclass
 */
class DDC964User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="user_id")
     */
    protected $id;

    /**
     * @ORM\Column(name="user_name", nullable=true, unique=false, length=250)
     */
    protected $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="DDC964Group", inversedBy="users", cascade={"persist", "merge", "detach"})
     * @ORM\JoinTable(name="ddc964_users_groups",
     *  joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * @var DDC964Address
     *
     * @ORM\ManyToOne(targetEntity="DDC964Address", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
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

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
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

        $association = new Mapping\ManyToOneAssociationMetadata('address');

        $association->setJoinColumns($joinColumns);
        $association->setTargetEntity('DDC964Address');
        $association->setCascade(['persist', 'merge']);

        $metadata->addProperty($association);

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

        $association = new Mapping\ManyToManyAssociationMetadata('groups');

        $association->setJoinTable($joinTable);
        $association->setTargetEntity('DDC964Group');
        $association->setInversedBy('users');
        $association->setCascade(['persist', 'merge', 'detach']);

        $metadata->addProperty($association);

        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
    }
}
