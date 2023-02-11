<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ToOneAssociationMapping extends AssociationMapping
{
    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;

    /** @var list<JoinColumnData> */
    public array|null $joinColumns = null;

    /** @return mixed[] */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ($array['joinColumns'] !== null) {
            $joinColumns = [];
            foreach ($array['joinColumns'] as $column) {
                $joinColumns[] = (array) $column;
            }

            $array['joinColumns'] = $joinColumns;
        }

        return $array;
    }
}
