<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_onetoone")
 */
class OwningOneToOneEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id2;

    /**
     * @var InversedOneToOneEntity
     * @OneToOne(targetEntity="InversedOneToOneEntity", inversedBy="associatedEntity")
     * @JoinColumn(name="associated_id", referencedColumnName="id1")
     */
    public $associatedEntity;
}
