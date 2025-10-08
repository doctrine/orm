<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\SqlFinalizer;
use LogicException;

/**
 * Encapsulates the resulting components from a DQL query parsing process that
 * can be serialized.
 *
 * @link        http://www.doctrine-project.org
 */
class ParserResult
{
    /**
     * The SQL executor used for executing the SQL.
     */
    private SqlFinalizer|null $sqlFinalizer = null;

    /**
     * The ResultSetMapping that describes how to map the SQL result set.
     */
    private ResultSetMapping $resultSetMapping;

    /**
     * The mappings of DQL parameter names/positions to SQL parameter positions.
     *
     * @phpstan-var array<string|int, list<int>>
     */
    private array $parameterMappings = [];

    /**
     * Initializes a new instance of the <tt>ParserResult</tt> class.
     * The new instance is initialized with an empty <tt>ResultSetMapping</tt>.
     */
    public function __construct()
    {
        $this->resultSetMapping = new ResultSetMapping();
    }

    /**
     * Gets the ResultSetMapping for the parsed query.
     *
     * @return ResultSetMapping The result set mapping of the parsed query
     */
    public function getResultSetMapping(): ResultSetMapping
    {
        return $this->resultSetMapping;
    }

    /**
     * Sets the ResultSetMapping of the parsed query.
     */
    public function setResultSetMapping(ResultSetMapping $rsm): void
    {
        $this->resultSetMapping = $rsm;
    }

    public function setSqlFinalizer(SqlFinalizer $finalizer): void
    {
        $this->sqlFinalizer = $finalizer;
    }

    public function prepareSqlExecutor(Query $query): AbstractSqlExecutor
    {
        if ($this->sqlFinalizer === null) {
            throw new LogicException('No SqlFinalizer has been set; this ParserResult is incomplete.');
        }

        return $this->sqlFinalizer->createExecutor($query);
    }

    /**
     * Adds a DQL to SQL parameter mapping. One DQL parameter name/position can map to
     * several SQL parameter positions.
     */
    public function addParameterMapping(string|int $dqlPosition, int $sqlPosition): void
    {
        $this->parameterMappings[$dqlPosition][] = $sqlPosition;
    }

    /**
     * Gets all DQL to SQL parameter mappings.
     *
     * @phpstan-return array<int|string, list<int>> The parameter mappings.
     */
    public function getParameterMappings(): array
    {
        return $this->parameterMappings;
    }

    /**
     * Gets the SQL parameter positions for a DQL parameter name/position.
     *
     * @param string|int $dqlPosition The name or position of the DQL parameter.
     *
     * @return int[] The positions of the corresponding SQL parameters.
     * @phpstan-return list<int>
     */
    public function getSqlParameterPositions(string|int $dqlPosition): array
    {
        return $this->parameterMappings[$dqlPosition];
    }
}
