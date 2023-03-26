<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * The "many" side of a many-to-one association mapping is always the owning side.
 */
final class ManyToOneAssociationMapping extends ToOneAssociationMapping implements AssociationOwningSideMapping
{
    /** @var list<JoinColumnMapping> */
    public array $joinColumns = [];

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = parent::__sleep();

        $serialized[] = 'joinColumns';

        return $serialized;
    }
}
