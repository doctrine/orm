<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @Entity
 */
class DDC889Entity extends DDC889SuperClass
{
    public static function loadMetadata(ClassMetadataInfo $metadata): void
    {
    }
}
