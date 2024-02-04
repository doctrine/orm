<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_extralazy")
 */
class InversedManyToManyExtraLazyEntity
{
    /**
     * @var string
     * @Column(type="rot13", length=255)
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
