<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\AST;

/**
 * Executor that executes the SQL statements for DQL DELETE/UPDATE statements on classes
 * that are mapped to a single table.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @author      Roman Borschel <roman@code-factory.org>
 * @link        www.doctrine-project.org
 * @since       2.0
 * @todo This is exactly the same as SingleSelectExecutor. Unify in SingleStatementExecutor.
 */
class SingleTableDeleteUpdateExecutor extends AbstractSqlExecutor
{
    /**
     * @param \Doctrine\ORM\Query\AST\Node  $AST
     * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
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
