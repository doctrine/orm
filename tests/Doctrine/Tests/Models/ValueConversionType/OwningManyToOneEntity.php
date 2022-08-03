<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_manytoone")
 */
class OwningManyToOneEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @var InversedOneToManyEntity
     * @ManyToOne(targetEntity="InversedOneToManyEntity", inversedBy="associatedEntities")
     * @JoinColumn(name="associated_id", referencedColumnName="id1")
     */
    public $associatedEntity;
}
