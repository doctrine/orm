<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_manytoone_compositeid_foreignkey")
 */
class OwningManyToOneCompositeIdForeignKeyEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyCompositeIdForeignKeyEntity", inversedBy="associatedEntities")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id", referencedColumnName="id1"),
     *     @JoinColumn(name="associated_foreign_id", referencedColumnName="foreign_id")
     * })
     */
    public $associatedEntity;
}
