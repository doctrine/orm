<?php

namespace Doctrine\Tests\Models\GeoNames;

/**
 * @Entity
 * @Table(name="geonames_admin1_alternate_name")
 * @Cache
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
     * @Cache
     */
    public $admin1;

    /**
     * @Column(type="string", length=255);
     */
    public $name;


    public function __construct($id, $name, Admin1 $admin1)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->admin1 = $admin1;
    }
}
