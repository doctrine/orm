<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_admin1")
 * @Cache
 */
class Admin1
{
    /**
     * @Id
     * @Column(type="integer", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @Id
     * @ManyToOne(targetEntity="Country")
     * @JoinColumn(name="country", referencedColumnName="id")
     * @Cache
     */
    public $country;

    /**
     * @OneToMany(targetEntity="Admin1AlternateName", mappedBy="admin1")
     * @Cache
     */
    public $names = [];

    /**
     * @Column(type="string", length=255);
     */
    public $name;

    public function __construct($id, $name, Country $country)
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->country = $country;
    }
}
