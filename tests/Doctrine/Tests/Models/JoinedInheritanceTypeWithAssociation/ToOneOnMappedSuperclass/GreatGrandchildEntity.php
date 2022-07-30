<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ToOneOnMappedSuperclass;

use Doctrine\ORM\Mapping\Entity;

/**
 * @Entity
 */
class GreatGrandchildEntity extends GrandchildMappedSuperclass
{
}
