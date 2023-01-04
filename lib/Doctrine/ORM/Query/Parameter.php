<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use function trim;

/**
 * Defines a Query Parameter.
 *
 * @link    www.doctrine-project.org
 */
class Parameter
{
    /**
     * Returns the internal representation of a parameter name.
     */
    public static function normalizeName(int|string $name): string
    {
        return trim((string) $name, ':');
    }

    /**
     * The parameter name.
     */
    private readonly string $name;

    /**
     * The parameter value.
     */
    private mixed $value;

    /**
     * The parameter type.
     */
    private mixed $type;

    /**
     * Whether the parameter type was explicitly specified or not
     */
    private readonly bool $typeSpecified;

    public function __construct(int|string $name, mixed $value, mixed $type = null)
    {
        $this->name          = self::normalizeName($name);
        $this->typeSpecified = $type !== null;

        $this->setValue($value, $type);
    }

    /**
     * Retrieves the Parameter name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the Parameter value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Retrieves the Parameter type.
     */
    public function getType(): mixed
    {
        return $this->type;
    }

    /**
     * Defines the Parameter value.
     */
    public function setValue(mixed $value, mixed $type = null): void
    {
        $this->value = $value;
        $this->type  = $type ?: ParameterTypeInferer::inferType($value);
    }

    public function typeWasSpecified(): bool
    {
        return $this->typeSpecified;
    }
}
