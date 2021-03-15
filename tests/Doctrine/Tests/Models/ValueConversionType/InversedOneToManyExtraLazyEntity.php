<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany_extralazy")
 */
class InversedOneToManyExtraLazyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @var Collection<int, OwningManyToOneExtraLazyEntity>
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
