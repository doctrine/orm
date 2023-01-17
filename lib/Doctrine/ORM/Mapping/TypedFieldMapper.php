<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use ReflectionProperty;

interface TypedFieldMapper
{
    /**
     * Validates & completes the given field mapping based on typed property.
     *
     * @param array{fieldName: string, enumType?: class-string<BackedEnum>, type?: string} $mapping The field mapping to validate & complete.
     *
     * @return array{fieldName: string, enumType?: class-string<BackedEnum>, type?: string} The updated mapping.
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array;
}
