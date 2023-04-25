<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ReflectionProperty;

final class ChainTypedFieldMapper implements TypedFieldMapper
{
    /**
     * @readonly
     * @var TypedFieldMapper[] $typedFieldMappers
     */
    private array $typedFieldMappers;

    public function __construct(TypedFieldMapper ...$typedFieldMappers)
    {
        $this->typedFieldMappers = $typedFieldMappers;
    }

    /**
     * {@inheritDoc}
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array
    {
        foreach ($this->typedFieldMappers as $typedFieldMapper) {
            $mapping = $typedFieldMapper->validateAndComplete($mapping, $field);
        }

        return $mapping;
    }
}
