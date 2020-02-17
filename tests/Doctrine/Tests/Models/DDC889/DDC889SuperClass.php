<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\MappedSuperclass
 */
class DDC889SuperClass
{
    /** @ORM\Column() */
    protected $name;
}
