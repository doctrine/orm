<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

/**
 * A QuerySetMapping describes how the bind parameters of a query map to the type of the mapped
 * fields they are compared or assigned to. It is the counterpart of {@see ResultSetMapping}.
 *
 * Parameters are keyed the same way as {@see ParserResult::$parameterMappings}, by DQL parameter
 * name or position, so that both named and positional binding are covered.
 *
 * IMPORTANT NOTE: (same as for {@see ResultSetMapping})
 * The properties of this class are only public for fast internal READ access and to (drastically)
 * reduce the size of serialized instances for more effective caching due to better (un-)serialization
 * performance.
 *
 * <b>Users should use the public methods.</b>
 *
 * @see ResultSetMapping
 */
class QuerySetMapping
{
    /**
     * Maps DQL parameters to the name of the DBAL type of the field they are compared or
     * assigned to.
     *
     * @ignore
     * @var array<string|int, string>
     */
    public array $typeMappings = [];

    /**
     * DQL parameters that were used against fields of more than one type. Nothing can be inferred
     * for those, so they are tracked apart and never mapped again.
     *
     * @ignore
     * @var array<string|int, true>
     */
    public array $ambiguousParameters = [];

    /**
     * Adds a parameter mapping to this QuerySetMapping.
     *
     * @param string|int $parameter The name or position of the DQL parameter.
     * @param string     $type      The name of the DBAL type the field is mapped to.
     */
    public function addParameter(string|int $parameter, string $type): static
    {
        if (isset($this->ambiguousParameters[$parameter])) {
            return $this;
        }

        if (isset($this->typeMappings[$parameter])) {
            if ($this->typeMappings[$parameter] !== $type) {
                unset($this->typeMappings[$parameter]);

                $this->ambiguousParameters[$parameter] = true;
            }

            return $this;
        }

        $this->typeMappings[$parameter] = $type;

        return $this;
    }

    /**
     * Checks whether a type is mapped for the given DQL parameter.
     *
     * @param string|int $parameter The name or position of the DQL parameter.
     */
    public function hasParameter(string|int $parameter): bool
    {
        return isset($this->typeMappings[$parameter]);
    }

    /**
     * Gets the name of the DBAL type mapped for the given DQL parameter, if any.
     *
     * @param string|int $parameter The name or position of the DQL parameter.
     */
    public function getParameterType(string|int $parameter): string|null
    {
        return $this->typeMappings[$parameter] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->typeMappings === [];
    }
}
