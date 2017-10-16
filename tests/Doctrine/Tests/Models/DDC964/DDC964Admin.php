<?php

namespace Doctrine\Tests\Models\DDC964;

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
    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $metadata->setAssociationOverride('address',
            [
            'joinColumns'=> [
                [
                'name' => 'adminaddress_id',
                'referencedColumnName' => 'id',
                ]
            ]
            ]
        );

        $metadata->setAssociationOverride('groups',
            [
            'joinTable' => [
                'name'      => 'ddc964_users_admingroups',
                'joinColumns' => [
                    [
                    'name' => 'adminuser_id',
                    ]
                ],
                'inverseJoinColumns' => [[
                    'name'      => 'admingroup_id',
                ]]
            ]
            ]
        );
    }
}
