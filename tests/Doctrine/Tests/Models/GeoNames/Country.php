<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_country")
 */
class Country
{
    /**
     * @Id
     * @Column(type="string", length=2)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @Column(type="string", length=255);
     */
    public $name;

}
