<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ValidToManyOnRoot;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\RootEntity;

/**
 * @MappedSuperclass
 */
abstract class ChildMappedSuperclass extends RootEntity
{
}
