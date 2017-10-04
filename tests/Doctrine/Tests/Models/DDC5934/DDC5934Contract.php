<?php

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @Entity
 * @AssociationOverrides(
 *     @AssociationOverride(name="members", fetch="EXTRA_LAZY")
 * )
 */
class DDC5934Contract extends DDC5934BaseContract
{
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setAssociationOverride('members', [
            'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
        ]);
    }
}
