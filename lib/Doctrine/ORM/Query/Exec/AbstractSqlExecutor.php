<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Cache\QueryCacheProfile;

/**
 * Base class for SQL statement executors.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        http://www.doctrine-project.org
 * @since       2.0
 * @todo Rename: AbstractSQLExecutor
 */
abstract class AbstractSqlExecutor
{
    /**
     * @var array
     */
    protected $sqlStatements;

    /**
     * @var QueryCacheProfile
     */
    protected $queryCacheProfile;

    /**
     * Gets the SQL statements that are executed by the executor.
     *
     * @return array  All the SQL update statements.
     */
    public function getSqlStatements()
    {
        return $this->sqlStatements;
    }

    /**
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile $qcp
     *
     * @return void
     */
    public function setQueryCacheProfile(QueryCacheProfile $qcp)
    {
        $this->queryCacheProfile = $qcp;
    }

    /**
     * Do not use query cache
     *
     * @return void
     */
    public function removeQueryCacheProfile()
    {
        $this->queryCacheProfile = null;
    }

    /**
     * Executes all sql statements.
     *
     * @param Connection $conn   The database connection that is used to execute the queries.
     * @param array      $params The parameters.
     * @param array      $types  The parameter types.
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    abstract public function execute(Connection $conn, array $params, array $types);
}
