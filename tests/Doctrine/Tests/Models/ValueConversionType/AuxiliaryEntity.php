<?php

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_auxiliary")
 */
class AuxiliaryEntity
{
    /**
     * @Column(type="rot13")
     * @Id
     */
    public $id4;
}
