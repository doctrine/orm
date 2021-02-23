<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Executor that executes the SQL statements for DQL DELETE/UPDATE statements on classes
 * that are mapped to a single table.
 */
class SingleTableDeleteUpdateExecutor extends AbstractSqlExecutor
{
    /**
     * @param SqlWalker $sqlWalker
     */
    public function __construct(AST\Node $AST, $sqlWalker)
    {
        if ($AST instanceof AST\UpdateStatement) {
            $this->sqlStatements = $sqlWalker->walkUpdateStatement($AST);
        } elseif ($AST instanceof AST\DeleteStatement) {
            $this->sqlStatements = $sqlWalker->walkDeleteStatement($AST);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types)
    {
        return $conn->executeUpdate($this->sqlStatements, $params, $types);
    }
}
