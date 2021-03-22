<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_manytomany_compositeid_foreignkey")
 */
class InversedManyToManyCompositeIdForeignKeyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @var AuxiliaryEntity
     * @ManyToOne(targetEntity="AuxiliaryEntity")
     * @JoinColumn(name="foreign_id", referencedColumnName="id4")
     * @Id
     */
    public $foreignEntity;

    /**
     * @psalm-var Collection<int, OwningManyToManyCompositeIdForeignKeyEntity>
     * @ManyToMany(targetEntity="OwningManyToManyCompositeIdForeignKeyEntity", mappedBy="associatedEntities")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
