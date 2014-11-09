<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_compositeid")
 */
class InversedManyToManyCompositeIdEntity
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
     * @ManyToMany(targetEntity="OwningManyToManyCompositeIdEntity", mappedBy="associatedEntities")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
