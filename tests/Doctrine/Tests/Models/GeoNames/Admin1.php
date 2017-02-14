<?php

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="geonames_admin1")
 * @ORM\Cache
 */
class Admin1
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", length=25)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     * @ORM\Cache
     */
    public $country;

    /**
     * @ORM\OneToMany(targetEntity="Admin1AlternateName", mappedBy="admin1")
     * @ORM\Cache
     */
    public $names = [];

    /**
     * @ORM\Column(type="string", length=255);
     */
    public $name;

    public function __construct($id, $name, Country $country)
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->country = $country;
    }
}
