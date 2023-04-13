<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class OneToOneOwningSideMapping extends OneToOneAssociationMapping implements AssociationOwningSideMapping
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
