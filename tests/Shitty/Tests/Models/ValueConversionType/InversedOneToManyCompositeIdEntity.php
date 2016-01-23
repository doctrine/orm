<?php

namespace Shitty\Tests\Models\ValueConversionType;

use Shitty\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany_compositeid")
 */
class InversedOneToManyCompositeIdEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @OneToMany(targetEntity="OwningManyToOneCompositeIdEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
