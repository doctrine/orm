<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class ManyToManyOwningSideMapping extends ManyToManyAssociationMapping implements AssociationOwningSideMapping
{
    /**
     * Specification of the join table and its join columns (foreign keys).
     * Only valid for many-to-many mappings. Note that one-to-many associations
     * can be mapped through a join table by simply mapping the association as
     * many-to-many with a unique constraint on the join table.
     */
    public JoinTableMapping $joinTable;

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['joinTable'] = $this->joinTable->toArray();

        return $array;
    }
}
