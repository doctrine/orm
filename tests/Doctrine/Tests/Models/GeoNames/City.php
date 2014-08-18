<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_city")
 */
class City
{
    /**
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Country", inversedBy="cities")
     * @JoinColumn(name="country", referencedColumnName="id")
     */
    private $country;

    /**
     * @ManyToOne(targetEntity="Admin1", inversedBy="cities")
     * @JoinColumn(name="country", referencedColumnName="id")
     */
    private $country;

    /**
     * @Column(type="string", length=255);
     */
    private $name;

}
