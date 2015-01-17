<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany_extralazy")
 */
class InversedOneToManyExtraLazyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @OneToMany(
     *     targetEntity="OwningManyToOneExtraLazyEntity",
     *     mappedBy="associatedEntity",
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
