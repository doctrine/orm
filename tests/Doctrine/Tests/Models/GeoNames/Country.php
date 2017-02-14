<?php

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="geonames_country")
 * @ORM\Cache
 */
class Country
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=2)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $name;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
