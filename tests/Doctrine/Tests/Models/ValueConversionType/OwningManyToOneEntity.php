<?php

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_manytoone")
 */
class OwningManyToOneEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @ManyToOne(targetEntity="InversedOneToManyEntity", inversedBy="associatedEntities")
     * @JoinColumn(name="associated_id", referencedColumnName="id1")
     */
    public $associatedEntity;
}
