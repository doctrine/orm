<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="vct_auxiliary")
 */
class AuxiliaryEntity
{
    /**
     * @var string
     * @Column(type="rot13", length=255)
     * @Id
     */
    public $id4;
}
