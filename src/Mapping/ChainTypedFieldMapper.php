<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\ORM\Internal\NoUnknownNamedArguments;
use ReflectionProperty;

final class ChainTypedFieldMapper implements TypedFieldMapper
{
    use NoUnknownNamedArguments;

    /** @var list<TypedFieldMapper> $typedFieldMappers */
    private readonly array $typedFieldMappers;

    public function __construct(TypedFieldMapper ...$typedFieldMappers)
    {
        self::validateVariadicParameter($typedFieldMappers);

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
