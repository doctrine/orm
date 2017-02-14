<?php

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="vct_auxiliary")
 */
class AuxiliaryEntity
{
    /**
     * @ORM\Column(type="rot13")
     * @ORM\Id
     */
    public $id4;
}
