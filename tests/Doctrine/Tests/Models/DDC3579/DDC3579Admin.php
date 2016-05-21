<?php

namespace Doctrine\Tests\Models\DDC3579;

/**
 * @Entity
 * @AssociationOverrides({
 *      @AssociationOverride(
 *          name="groups",
 *          inversedBy="admins"
 *      )
 * })
 */
class DDC3579Admin extends DDC3579User
{
    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->setAssociationOverride('groups', [
            'inversedBy' => 'admins'
        ]
        );
    }
}
