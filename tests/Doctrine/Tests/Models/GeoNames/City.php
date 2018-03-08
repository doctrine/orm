<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="geonames_city")
 * @ORM\Cache
 */
class City
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=25)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     * @ORM\Cache
     */
    public $country;

    /**
     * @ORM\ManyToOne(targetEntity=Admin1::class)
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="admin1", referencedColumnName="id"),
     *   @ORM\JoinColumn(name="country", referencedColumnName="country")
     * })
     * @ORM\Cache
     */
    public $admin1;

    /** @ORM\Column(type="string", length=255) */
    public $name;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
