<?php

namespace Doctrine\Tests\Models\DDC2645;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * @Entity
 * @Table(name="ddc2645_variant")
 */
class DDC2645Variant
{
    /**
     * @Column(type="string")
     * @Id
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="DDC2645Price", mappedBy="variant", cascade={"all"})
     */
    public $prices;
}
