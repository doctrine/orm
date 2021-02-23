<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

/**
 * Base class for SQL statement executors.
 *
 * @todo Rename: AbstractSQLExecutor
 */
abstract class AbstractSqlExecutor
{
    /** @var string[] */
    protected $sqlStatements;

    /** @var QueryCacheProfile */
    protected $queryCacheProfile;

    /**
     * Gets the SQL statements that are executed by the executor.
     *
     * @return string[] All the SQL update statements.
     */
    public function getSqlStatements()
    {
        return $this->sqlStatements;
    }

    public function setQueryCacheProfile(QueryCacheProfile $qcp)
    {
        $this->queryCacheProfile = $qcp;
    }

    /**
     * Do not use query cache
     */
    public function removeQueryCacheProfile()
    {
        $this->queryCacheProfile = null;
    }

    /**
     * Executes all sql statements.
     *
     * @param Connection $conn   The database connection that is used to execute the queries.
     * @param mixed[]    $params The parameters.
     * @param mixed[]    $types  The parameter types.
     *
     * @return Statement
     */
    abstract public function execute(Connection $conn, array $params, array $types);
}
