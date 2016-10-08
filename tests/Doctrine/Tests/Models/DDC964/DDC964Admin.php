<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Mapping;

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
    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $joinColumns = array();

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('adminaddress_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('');

        $joinColumns[] = $joinColumn;

        $metadata->setAssociationOverride('address', array(
            'joinColumns' => $joinColumns,
        ));


        $joinTable = new Mapping\JoinTableMetadata();

        $joinTable->setName('ddc964_users_admingroups');

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('adminuser_id');

        $joinTable->addJoinColumn($joinColumn);

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('admingroup_id');

        $joinTable->addInverseJoinColumn($joinColumn);

        $metadata->setAssociationOverride('groups', array(
            'joinTable' => $joinTable,
        ));
    }
}