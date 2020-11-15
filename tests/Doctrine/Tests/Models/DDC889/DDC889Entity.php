<?php

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity
 */
#[ORM\Entity]
class DDC889Entity extends DDC889SuperClass
{

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
    }

}
