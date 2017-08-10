<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="groups",
 *          joinTable=@ORM\JoinTable(
 *              name="ddc964_users_admingroups",
 *              joinColumns=@ORM\JoinColumn(name="adminuser_id"),
 *              inverseJoinColumns=@ORM\JoinColumn(name="admingroup_id")
 *          )
 *      ),
 *      @ORM\AssociationOverride(
 *          name="address",
 *          joinColumns=@ORM\JoinColumn(
 *              name="adminaddress_id", referencedColumnName="id"
 *          )
 *      )
 * })
 */
class DDC964Admin extends DDC964User
{
}
