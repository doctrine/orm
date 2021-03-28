<?php

declare(strict_types=1);

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
    public static function loadMetadata($metadata): void
    {
        $metadata->setAssociationOverride('groups', ['inversedBy' => 'admins']);
    }
}
