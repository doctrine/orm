<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(name="groups",
 *          joinTable=@ORM\JoinTable(
 *              name="ddc964_users_admingroups",
 *              joinColumns=@ORM\JoinColumn(name="adminuser_id"),
 *              inverseJoinColumns=@ORM\JoinColumn(name="admingroup_id")
 *          )
 *      ),
 *      @ORM\AssociationOverride(name="address",
 *          joinColumns=@ORM\JoinColumn(
 *              name="adminaddress_id", referencedColumnName="id"
 *          )
 *      )
 * })
 */
class DDC964Admin extends DDC964User
{
    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $joinColumns = [];

        $joinColumn = new Mapping\JoinColumnMetadata();

        $joinColumn->setColumnName('adminaddress_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('');

        $joinColumns[] = $joinColumn;

        $association = new Mapping\ManyToOneAssociationMetadata('address');

        $association->setJoinColumns($joinColumns);

        $metadata->setAssociationOverride($association);

        $joinTable = new Mapping\JoinTableMetadata();
        $joinTable->setName('ddc964_users_admingroups');

        $joinColumn = new Mapping\JoinColumnMetadata();
        $joinColumn->setColumnName('adminuser_id');

        $joinTable->addJoinColumn($joinColumn);

        $joinColumn = new Mapping\JoinColumnMetadata();
        $joinColumn->setColumnName('admingroup_id');

        $joinTable->addInverseJoinColumn($joinColumn);

        $association = new Mapping\ManyToManyAssociationMetadata('groups');

        $association->setJoinTable($joinTable);

        $metadata->setAssociationOverride($association);
    }
}
