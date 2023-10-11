<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function get_debug_type;

final class EntityMissingAssignedId extends LogicException implements ORMException
{
    public static function forField(object $entity, string $field): self
    {
        return new self('Entity of type ' . get_debug_type($entity) . " is missing an assigned ID for field  '" . $field . "'. " .
            'The identifier generation strategy for this entity requires the ID field to be populated before ' .
            'EntityManager#persist() is called. If you want automatically generated identifiers instead ' .
            'you need to adjust the metadata mapping accordingly.');
    }
}
