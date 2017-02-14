<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_traveler_profile_info")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 */
class TravelerProfileInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(unique=true)
     */
    private $description;

    /**
     * @ORM\Cache()
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     * @ORM\OneToOne(targetEntity="TravelerProfile", inversedBy="info")
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
