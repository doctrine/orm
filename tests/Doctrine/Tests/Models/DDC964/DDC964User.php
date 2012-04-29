<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MappedSuperclass
 */
class DDC964User
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

        $metadata->mapManyToOne(array(
           'fieldName'      => 'address',
           'targetEntity'   => 'DDC964Address',
           'cascade'        => array('persist','merge'),
           'joinColumn'     => array('name'=>'address_id', 'referencedColumnMame'=>'id'),
        ));

        $metadata->mapManyToMany(array(
           'fieldName'      => 'groups',
           'targetEntity'   => 'DDC964Group',
           'inversedBy'     => 'users',
           'cascade'        => array('persist','merge','detach'),
           'joinTable'      => array(
                'name'          => 'ddc964_users_groups',
                'joinColumns'   => array(array(
                    'name'=>'user_id',
                    'referencedColumnName'=>'id',
                )),
                'inverseJoinColumns'=>array(array(
                    'name'=>'group_id',
                    'referencedColumnName'=>'id',
                ))
           )
        ));

        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_AUTO);
    }
}