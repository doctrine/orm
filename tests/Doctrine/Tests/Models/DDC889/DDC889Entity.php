<?php

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 */
class DDC889Entity extends DDC889SuperClass
{
    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
    }
}
