<?php

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

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

    /**
     * @Column(name="user_name", nullable=true, unique=false, length=250)
     */
    protected $name;

    /**
     * @var ArrayCollection
     *
     * @ManyToMany(targetEntity="DDC3579Group")
     */
    protected $groups;

    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->name     = $name;
        $this->groups   = new ArrayCollection;
    }

    /**
     * @return integer
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
     * @param DDC3579Group $group
     */
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

        $metadata->mapManyToMany(
            [
               'fieldName'      => 'groups',
               'targetEntity'   => 'DDC3579Group'
            ]
        );

        $metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
    }
}
