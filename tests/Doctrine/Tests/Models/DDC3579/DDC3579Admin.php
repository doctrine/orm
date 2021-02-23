<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="groups",
 *          inversedBy="admins"
 *      )
 * })
 */
class DDC3579Admin extends DDC3579User
{
}
