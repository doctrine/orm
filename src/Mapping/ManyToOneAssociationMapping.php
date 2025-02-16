<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * The "many" side of a many-to-one association mapping is always the owning side.
 */
final class ManyToOneAssociationMapping extends ToOneOwningSideMapping
{
}
