<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany")
 */
class InversedManyToManyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @psalm-var Collection<int, OwningManyToManyEntity>
     * @ManyToMany(targetEntity="OwningManyToManyEntity", mappedBy="associatedEntities")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
