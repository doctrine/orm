<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_owning_manytomany_extralazy")
 */
class OwningManyToManyExtraLazyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @var Collection<int, InversedManyToManyExtraLazyEntity>
     * @ManyToMany(
     *     targetEntity="InversedManyToManyExtraLazyEntity",
     *     inversedBy="associatedEntities",
     *     fetch="EXTRA_LAZY",
     *     indexBy="id1"
     * )
     * @JoinTable(
     *     name="vct_xref_manytomany_extralazy",
     *     joinColumns={@JoinColumn(name="owning_id", referencedColumnName="id2")},
     *     inverseJoinColumns={@JoinColumn(name="inversed_id", referencedColumnName="id1")}
     * )
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
