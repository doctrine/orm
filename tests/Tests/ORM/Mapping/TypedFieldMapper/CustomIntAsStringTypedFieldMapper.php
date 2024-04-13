<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\TypedFieldMapper;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\TypedFieldMapper;
use ReflectionNamedType;
use ReflectionProperty;

final class CustomIntAsStringTypedFieldMapper implements TypedFieldMapper
{
    /**
     * {@inheritDoc}
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array
    {
        $type = $field->getType();

        if (
            ! isset($mapping['type'])
            && ($type instanceof ReflectionNamedType)
        ) {
            if ($type->getName() === 'int') {
                $mapping['type'] = Types::STRING;
            }
        }

        return $mapping;
    }
}
