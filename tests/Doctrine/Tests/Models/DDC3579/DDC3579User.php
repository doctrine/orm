<?php

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;

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

    public static function loadMetadata($metadata)
    {
        $metadata->mapField(array(
           'id'         => true,
           'fieldName'  => 'id',
           'type'       => 'integer',
           'columnName' => 'user_id',
           'length'     => 150,
        ));

        $metadata->mapField(array(
            'fieldName' => 'name',
            'type'      => 'string',
            'columnName'=> 'user_name',
            'nullable'  => true,
            'unique'    => false,
            'length'    => 250,
        ));

        $metadata->mapManyToMany(array(
           'fieldName'      => 'groups',
           'targetEntity'   => 'DDC3579Group'
        ));

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_AUTO);
    }
}
