<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

/**
 * SQL executor for a given, final, single SELECT SQL query
 */
class FinalizedSelectExecutor extends AbstractSqlExecutor
{
    public function __construct(string $sql)
    {
        parent::__construct();

        $this->sqlStatements = $sql;
    }

    public function execute(Connection $conn, array $params, array $types): Result
    {
        return $conn->executeQuery($this->getSqlStatements(), $params, $types, $this->queryCacheProfile);
    }
}
