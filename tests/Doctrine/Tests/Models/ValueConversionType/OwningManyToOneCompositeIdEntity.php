<?php

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_manytoone_compositeid")
 */
class OwningManyToOneCompositeIdEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id3;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyCompositeIdEntity", inversedBy="associatedEntities")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id1", referencedColumnName="id1"),
     *     @JoinColumn(name="associated_id2", referencedColumnName="id2")
     * })
     */
    public $associatedEntity;
}
