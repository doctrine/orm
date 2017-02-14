<?php

namespace Doctrine\Tests\Models\GeoNames;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="geonames_admin1_alternate_name")
 * @ORM\Cache
 */
class Admin1AlternateName
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=25)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Admin1", inversedBy="names")
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(name="admin1", referencedColumnName="id"),
     *    @ORM\JoinColumn(name="country", referencedColumnName="country")
     * })
     * @ORM\Cache
     */
    public $admin1;

    /**
     * @ORM\Column(type="string", length=255);
     */
    public $name;

    public function __construct($id, $name, Admin1 $admin1)
    {
        $this->id     = $id;
        $this->name   = $name;
        $this->admin1 = $admin1;
    }
}
