<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

/**
 * @Entity
 * @Table(name="vct_auxiliary")
 */
class AuxiliaryEntity
{
    /**
     * @var string
     * @Column(type="rot13")
     * @Id
     */
    public $id4;
}
