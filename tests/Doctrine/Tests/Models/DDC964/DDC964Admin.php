<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @AssociationOverrides({
 *      @AssociationOverride(name="groups",
 *          joinTable=@JoinTable(
 *              name="ddc964_users_admingroups",
 *              joinColumns=@JoinColumn(name="adminuser_id"),
 *              inverseJoinColumns=@JoinColumn(name="admingroup_id")
 *          )
 *      ),
 *      @AssociationOverride(name="address",
 *          joinColumns=@JoinColumn(
 *              name="adminaddress_id", referencedColumnName="id"
 *          )
 *      )
 * })
 */
class DDC964Admin extends DDC964User
{
    public static function loadMetadata($metadata)
    {
        $metadata->setAssociationOverride('address',array(
            'joinColumns'=>array(array(
                'name' => 'adminaddress_id',
                'referencedColumnName' => 'id',
            ))
        ));

        $metadata->setAssociationOverride('groups',array(
            'joinTable' => array(
                'name'      => 'ddc964_users_admingroups',
                'joinColumns' => array(array(
                    'name' => 'adminuser_id',
                )),
                'inverseJoinColumns' =>array (array (
                    'name'      => 'admingroup_id',
                ))
            )
        ));
    }
}