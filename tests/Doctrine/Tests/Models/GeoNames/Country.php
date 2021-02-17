<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_country")
 * @Cache
 */
class Country
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=2)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255);
     */
    public $name;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
