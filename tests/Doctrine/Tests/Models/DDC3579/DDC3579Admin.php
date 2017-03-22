<?php

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

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
    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $association = new Mapping\ManyToManyAssociationMetadata('groups');

        $association->setInversedBy('admins');

        $metadata->setPropertyOverride($association);
    }
}
