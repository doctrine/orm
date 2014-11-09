<?php

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_onetoone")
 */
class OwningOneToOneEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id;

    /**
     * @OneToOne(targetEntity="InversedOneToOneEntity", inversedBy="associatedEntity")
     * @JoinColumn(name="associated_id", referencedColumnName="id")
     */
    public $associatedEntity;
}
