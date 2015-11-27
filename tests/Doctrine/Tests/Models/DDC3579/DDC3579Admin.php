<?php

namespace Doctrine\Tests\Models\DDC3579;

use Doctrine\Common\Collections\ArrayCollection;

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
    public static function loadMetadata($metadata)
    {
        $metadata->setAssociationOverride('groups', array(
            'inversedBy' => 'admins'
        ));
    }
}
