<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3699;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\MappedSuperClass */
abstract class DDC3699Parent
{
    /** @ORM\Column(type="string") */
    public $parentField;
}
