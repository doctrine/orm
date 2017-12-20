<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides(
 *     @ORM\AssociationOverride(name="members", fetch="EXTRA_LAZY")
 * )
 */
class DDC5934Contract extends DDC5934BaseContract
{
}
