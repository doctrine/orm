<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_admin1_alternate_name")
 */
class Admin1AlternateName
{
    /**
     * @Id
     * @Column(type="string", length=25)
     * @GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="Admin1", inversedBy="names")
     * @JoinColumns({
     *    @JoinColumn(name="admin1", referencedColumnName="id"),
     *    @JoinColumn(name="country", referencedColumnName="country")
     * })
     */
    public $admin1;

    /**
     * @Column(type="string", length=255);
     */
    public $name;

}
