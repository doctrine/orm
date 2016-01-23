<?php

namespace Shitty\Tests\Models\ValueConversionType;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany")
 */
class InversedOneToManyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @OneToMany(targetEntity="OwningManyToOneEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    /**
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
