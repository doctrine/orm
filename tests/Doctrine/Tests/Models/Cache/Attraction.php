<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 * @ORM\Entity
 * @ORM\Table("cache_attraction")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({
 *  1  = "Restaurant",
 *  2  = "Beach",
 *  3  = "Bar"
 * })
 */
abstract class Attraction
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
    protected $name;

    /**
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity="City", inversedBy="attractions")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     */
    protected $city;

    /**
     * @ORM\Cache
     * @ORM\OneToMany(targetEntity="AttractionInfo", mappedBy="attraction")
     */
    protected $infos;

    public function __construct($name, City $city)
    {
        $this->name  = $name;
        $this->city  = $city;
        $this->infos = new ArrayCollection();
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

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity(City $city)
    {
        $this->city = $city;
    }

    public function getInfos()
    {
        return $this->infos;
    }

    public function addInfo(AttractionInfo $info)
    {
        if ( ! $this->infos->contains($info)) {
            $this->infos->add($info);
        }
    }
}
