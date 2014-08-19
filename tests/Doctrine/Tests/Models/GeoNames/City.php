<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_city")
 * @Cache
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
     * @ManyToOne(targetEntity="Country")
     * @JoinColumn(name="country", referencedColumnName="id")
     * @Cache
     */
    private $country;

    /**
     * @ManyToOne(targetEntity="Admin1")
     * @JoinColumns({
     *   @JoinColumn(name="admin1", referencedColumnName="id"),
     *   @JoinColumn(name="country", referencedColumnName="country")
     * })
     * @Cache
     */
    private $admin1;

    /**
     * @Column(type="string", length=255);
     */
    private $name;


    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }


}
