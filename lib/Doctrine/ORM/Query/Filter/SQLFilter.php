<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ParameterTypeInferer;
use InvalidArgumentException;

use function array_map;
use function implode;
use function ksort;
use function serialize;

/**
 * The base class that user defined filters should extend.
 *
 * Handles the setting and escaping of parameters.
 *
 * @abstract
 */
abstract class SQLFilter
{
    /**
     * Parameters for the filter.
     *
     * @psalm-var array<string,array{type: string, value: mixed, is_list: bool}>
     */
    private array $parameters = [];

    final public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * Sets a parameter list that can be used by the filter.
     *
     * @param array<mixed> $values List of parameter values.
     * @param string       $type   The parameter type. If specified, the given value will be run through
     *                             the type conversion of this type.
     *
     * @return $this
     */
    final public function setParameterList(string $name, array $values, string $type = Types::STRING): static
    {
        $this->parameters[$name] = ['value' => $values, 'type' => $type, 'is_list' => true];

        // Keep the parameters sorted for the hash
        ksort($this->parameters);

        // The filter collection of the EM is now dirty
        $this->em->getFilters()->setFiltersStateDirty();

        return $this;
    }

    /**
     * Sets a parameter that can be used by the filter.
     *
     * @param string|null $type The parameter type. If specified, the given value will be run through
     *                          the type conversion of this type. This is usually not needed for
     *                          strings and numeric types.
     *
     * @return $this
     */
    final public function setParameter(string $name, mixed $value, ?string $type = null): static
    {
        if ($type === null) {
            $type = ParameterTypeInferer::inferType($value);
        }

        $this->parameters[$name] = ['value' => $value, 'type' => $type, 'is_list' => false];

        // Keep the parameters sorted for the hash
        ksort($this->parameters);

        // The filter collection of the EM is now dirty
        $this->em->getFilters()->setFiltersStateDirty();

        return $this;
    }

    /**
     * Gets a parameter to use in a query.
     *
     * The function is responsible for the right output escaping to use the
     * value in a query.
     *
     * @return string The SQL escaped parameter to use in a query.
     *
     * @throws InvalidArgumentException
     */
    final public function getParameter(string $name): string
    {
        if (! isset($this->parameters[$name])) {
            throw new InvalidArgumentException("Parameter '" . $name . "' does not exist.");
        }

        if ($this->parameters[$name]['is_list']) {
            throw FilterException::cannotConvertListParameterIntoSingleValue($name);
        }

        return $this->em->getConnection()->quote((string) $this->parameters[$name]['value']);
    }

    /**
     * Gets a parameter to use in a query assuming it's a list of entries.
     *
     * The function is responsible for the right output escaping to use the
     * value in a query, separating each entry by comma to inline it into
     * an IN() query part.
     *
     * @throws InvalidArgumentException
     */
    final public function getParameterList(string $name): string
    {
        if (! isset($this->parameters[$name])) {
            throw new InvalidArgumentException("Parameter '" . $name . "' does not exist.");
        }

        if ($this->parameters[$name]['is_list'] === false) {
            throw FilterException::cannotConvertSingleParameterIntoListValue($name);
        }

        $param      = $this->parameters[$name];
        $connection = $this->em->getConnection();

        $quoted = array_map(
            static fn (mixed $value): string => $connection->quote((string) $value),
            $param['value']
        );

        return implode(',', $quoted);
    }

    /**
     * Checks if a parameter was set for the filter.
     */
    final public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Returns as string representation of the SQLFilter parameters (the state).
     */
    final public function __toString(): string
    {
        return serialize($this->parameters);
    }

    /**
     * Returns the database connection used by the entity manager
     */
    final protected function getConnection(): Connection
    {
        return $this->em->getConnection();
    }

    /**
     * Gets the SQL query part to add to a query.
     *
     * @psalm-param ClassMetadata<object> $targetEntity
     *
     * @return string The constraint SQL if there is available, empty string otherwise.
     */
    abstract public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string;
}
