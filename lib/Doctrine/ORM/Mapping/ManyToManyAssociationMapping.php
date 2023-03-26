<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ManyToManyAssociationMapping extends ToManyAssociationMapping
{
    public array|null $relationToSourceKeyColumns = null;
    public array|null $relationToTargetKeyColumns = null;

    /** @return list<string> */
    public function __sleep(): array
    {
        $serialized = parent::__sleep();

        foreach (['relationToSourceKeyColumns', 'relationToTargetKeyColumns'] as $arrayKey) {
            if ($this->$arrayKey !== null) {
                $serialized[] = $arrayKey;
            }
        }

        return $serialized;
    }
}
