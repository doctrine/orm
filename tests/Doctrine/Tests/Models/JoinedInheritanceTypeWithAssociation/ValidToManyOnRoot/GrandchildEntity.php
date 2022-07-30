<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceTypeWithAssociation\ValidToManyOnRoot;

use Doctrine\ORM\Mapping\Entity;

/**
 * @Entity
 */
class GrandchildEntity extends ChildMappedSuperclass
{
}
