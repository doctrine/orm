<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function str_replace;

/**
 * Base class for SQL statement executors.
 *
 * @link        http://www.doctrine-project.org
 *
 * @todo Rename: AbstractSQLExecutor
 */
abstract class AbstractSqlExecutor
{
    /**
     * @deprecated use $sqlStatements instead
     *
     * @var list<string>|string
     */
    protected $_sqlStatements;

    /** @var list<string>|string */
    protected $sqlStatements;

    /** @var QueryCacheProfile */
    protected $queryCacheProfile;

    public function __construct()
    {
        // @phpstan-ignore property.deprecated
        $this->_sqlStatements = &$this->sqlStatements;
    }

    /**
     * Gets the SQL statements that are executed by the executor.
     *
     * @return list<string>|string  All the SQL update statements.
     */
    public function getSqlStatements()
    {
        return $this->sqlStatements;
    }

    public function setQueryCacheProfile(QueryCacheProfile $qcp): void
    {
        $this->queryCacheProfile = $qcp;
    }

    /**
     * Do not use query cache
     */
    public function removeQueryCacheProfile(): void
    {
        $this->queryCacheProfile = null;
    }

    /**
     * Executes all sql statements.
     *
     * @param Connection                                                           $conn   The database connection that is used to execute the queries.
     * @param list<mixed>|array<string, mixed>                                     $params The parameters.
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  The parameter types.
     *
     * @return Result|int
     */
    abstract public function execute(Connection $conn, array $params, array $types);

    /** @return list<string> */
    public function __sleep(): array
    {
        /* Two reasons for this:
           - we do not need to serialize the deprecated property, we can
             rebuild the reference to the new property in __wakeup()
           - not having the legacy property in the serialized data means the
             serialized representation becomes compatible with 3.0.x, meaning
             there will not be a deprecation warning about a missing property
             when unserializing data */
        return array_values(array_diff(array_map(static function (string $prop): string {
            return str_replace("\0*\0", '', $prop);
        }, array_keys((array) $this)), ['_sqlStatements']));
    }

    public function __wakeup(): void
    {
        // @phpstan-ignore property.deprecated
        if ($this->_sqlStatements !== null && $this->sqlStatements === null) {
            // @phpstan-ignore property.deprecated
            $this->sqlStatements = $this->_sqlStatements;
        }

        // @phpstan-ignore property.deprecated
        $this->_sqlStatements = &$this->sqlStatements;
    }
}
