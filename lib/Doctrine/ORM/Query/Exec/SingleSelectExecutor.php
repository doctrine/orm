<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Executor that executes the SQL statement for simple DQL SELECT statements.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @author      Roman Borschel <roman@code-factory.org>
 * @link        www.doctrine-project.org
 * @since       2.0
 */
class SingleSelectExecutor extends AbstractSqlExecutor
{
    /**
     * @param \Doctrine\ORM\Query\AST\SelectStatement $AST
     * @param \Doctrine\ORM\Query\SqlWalker           $sqlWalker
     */
    public function __construct(SelectStatement $AST, SqlWalker $sqlWalker)
    {
        $this->sqlStatements = $sqlWalker->walkSelectStatement($AST);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types)
    {
        return $conn->executeQuery($this->sqlStatements, $params, $types, $this->queryCacheProfile);
    }
}
