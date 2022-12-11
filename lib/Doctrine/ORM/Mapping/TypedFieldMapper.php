<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ReflectionProperty;

interface TypedFieldMapper
{
    /**
     * Validates & completes the given field mapping based on typed property.
     *
     * @param array{fieldName: string, enumType?: string, type?: mixed} $mapping The field mapping to validate & complete.
     *
     * @return array{fieldName: string, enumType?: string, type?: mixed} The updated mapping.
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array;
}
