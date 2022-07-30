<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\InvalidToManyOnMappedSuperclass;

use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * @MappedSuperclass
 */
abstract class GrandchildMappedSuperclass extends ChildMappedSuperclass
{
}
