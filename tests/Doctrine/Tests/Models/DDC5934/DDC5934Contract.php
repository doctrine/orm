<?php

declare(strict_types=1);

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
}
