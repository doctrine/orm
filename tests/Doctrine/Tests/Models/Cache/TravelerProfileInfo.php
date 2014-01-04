<?php

namespace Doctrine\Tests\Models\Cache;

/**
 * @Entity
 * @Table("cache_traveler_profile_info")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class TravelerProfileInfo
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
    private $description;

    /**
     * @Cache()
     * @JoinColumn(name="profile_id", referencedColumnName="id")
     * @OneToOne(targetEntity="TravelerProfile", inversedBy="info")
     */
    private $profile;

    public function __construct(TravelerProfile $profile, $description)
    {
        $this->profile     = $profile;
        $this->description = $description;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfile(TravelerProfile $profile)
    {
        $this->profile = $profile;
    }
}