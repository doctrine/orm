<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_extralazy")
 */
class InversedManyToManyExtraLazyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @var Collection<int, OwningManyToManyExtraLazyEntity>
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
