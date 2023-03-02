<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @internal
 *
 * @psalm-internal Doctrine\ORM\Mapping
 * @template       T of object
 * @template-extends ClassMetadata<T>
 */
class OverrideDisregardingClassMetadata extends ClassMetadata
{
    /**
     * @param string $fieldName
     * @psalm-param array<string, mixed> $overrideMapping
     */
    public function setAttributeOverride($fieldName, array $overrideMapping): void
    {
        // override to ignore consistency checks not relevant in this use case
    }

    /**
     * @param string $fieldName
     * @psalm-param array<string, mixed> $overrideMapping
     */
    public function setAssociationOverride($fieldName, array $overrideMapping): void
    {
        // override to ignore consistency checks not relevant in this use case
    }
}
