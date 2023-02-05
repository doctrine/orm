<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class ManyToManyAssociationMapping extends ToManyAssociationMapping
{
    /**
     * Specification of the join table and its join columns (foreign keys).
     * Only valid for many-to-many mappings. Note that one-to-many associations
     * can be mapped through a join table by simply mapping the association as
     * many-to-many with a unique constraint on the join table.
     */
    public JoinTableMapping|null $joinTable = null;

    public array|null $relationToSourceKeyColumns = null;
    public array|null $relationToTargetKeyColumns = null;

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['joinTable'] = $this->joinTable->toArray();

        return $array;
    }
}
