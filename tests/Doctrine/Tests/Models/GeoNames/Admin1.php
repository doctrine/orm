<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_admin1")
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
     * @ManyToOne(targetEntity="Country", inversedBy="admin1")
     * @JoinColumn(name="country", referencedColumnName="id")
     */
    public $country;

    /**
     * @OneToMany(targetEntity="Admin1AlternateName", mappedBy="admin1")
     */
    public $names = array();

    /**
     * @Column(type="string", length=255);
     */
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

}
