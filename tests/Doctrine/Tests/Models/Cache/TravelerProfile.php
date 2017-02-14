<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_traveler_profile")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 */
class TravelerProfile
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
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="TravelerProfileInfo", mappedBy="profile")
     * @ORM\Cache()
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
