<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\InvalidToManyOnMappedSuperclass;

use Doctrine\ORM\Mapping\Entity;

/**
 * @Entity
 */
class GreatGrandchildEntity extends GrandchildMappedSuperclass
{
}
