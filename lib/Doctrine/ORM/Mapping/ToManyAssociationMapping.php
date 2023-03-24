<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class ToManyAssociationMapping extends AssociationMapping
{
    /**
     * Specification of a field on target-entity that is used to index the
     * collection by. This field HAS to be either the primary key or a unique
     * column. Otherwise the collection does not contain all the entities that
     * are actually related.
     */
    public string|null $indexBy = null;

    /**
     * A map of field names (of the target entity) to sorting directions
     *
     * @var array<string, 'asc'|'desc'>|null
     */
    public array|null $orderBy = null;
}
