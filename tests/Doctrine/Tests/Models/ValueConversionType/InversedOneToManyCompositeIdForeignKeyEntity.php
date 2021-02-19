<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="vct_inversed_onetomany_compositeid_foreignkey")
 */
class InversedOneToManyCompositeIdForeignKeyEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id1;

    /**
     * @ManyToOne(targetEntity="AuxiliaryEntity")
     * @JoinColumn(name="foreign_id", referencedColumnName="id4")
     * @Id
     */
    public $foreignEntity;

    /**
     * @var string
     * @Column(type="string", name="some_property")
     */
    public $someProperty;

    /**
     * @psalm-var Collection<int, OwningManyToOneCompositeIdForeignKeyEntity>
     * @OneToMany(targetEntity="OwningManyToOneCompositeIdForeignKeyEntity", mappedBy="associatedEntity")
     */
    public $associatedEntities;

    public function __construct()
    {
        $this->associatedEntities = new ArrayCollection();
    }
}
