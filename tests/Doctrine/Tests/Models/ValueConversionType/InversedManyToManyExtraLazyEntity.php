<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_extralazy")
 */
class InversedManyToManyExtraLazyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @ManyToMany(
     *     targetEntity="OwningManyToManyExtraLazyEntity",
     *     mappedBy="associatedEntities",
     *     fetch="EXTRA_LAZY",
     *     indexBy="id2"
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
