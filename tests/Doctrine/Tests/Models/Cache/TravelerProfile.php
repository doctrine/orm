<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_traveler_profile")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class TravelerProfile
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(unique=true)
     */
    private $name;

    /**
     * @OneToOne(targetEntity="TravelerProfileInfo", mappedBy="profile")
     * @Cache()
     */
    private $info;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($nae)
    {
        $this->name = $nae;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function setInfo(TravelerProfileInfo $info)
    {
        $this->info = $info;
    }
}