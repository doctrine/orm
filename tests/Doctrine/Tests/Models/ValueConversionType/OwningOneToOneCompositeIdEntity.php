<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_owning_onetoone_compositeid")
 */
class OwningOneToOneCompositeIdEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id3;

    /**
     * @var InversedOneToOneCompositeIdEntity
     * @OneToOne(targetEntity="InversedOneToOneCompositeIdEntity", inversedBy="associatedEntity")
     * @JoinColumns({
     *     @JoinColumn(name="associated_id1", referencedColumnName="id1"),
     *     @JoinColumn(name="associated_id2", referencedColumnName="id2")
     * })
     */
    public $associatedEntity;
}
