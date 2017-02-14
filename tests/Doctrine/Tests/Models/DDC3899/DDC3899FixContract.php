<?php

namespace Doctrine\Tests\Models\DDC3899;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC3899FixContract extends DDC3899Contract
{
    /** @ORM\Column(type="integer") */
    public $fixPrice = 0;
}
