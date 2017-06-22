<?php

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides(
 *     @ORM\AssociationOverride(name="members", fetch="EXTRA_LAZY")
 * )
 */
class DDC5934Contract extends DDC5934BaseContract
{
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $association = new Mapping\ManyToManyAssociationMetadata('members');

        $association->setFetchMode(Mapping\FetchMode::EXTRA_LAZY);

        $metadata->setPropertyOverride($association);
    }
}
