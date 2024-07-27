<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Result;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Utility\LockSqlHelper;

/**
 * Executor that executes the SQL statement for simple DQL SELECT/UPDATE/DELETE statements.
 *
 * @link        www.doctrine-project.org
 */
class SingleStatementExecutor extends AbstractSqlExecutor
{
    use LockSqlHelper;

    private array $selectedClasses;

    public function __construct(AST\Node $AST, SqlWalker $sqlWalker)
    {
        $this->selectedClasses = $sqlWalker->getSelectedClasses();
        if ($AST instanceof AST\SelectStatement) {
            $this->sqlStatements = $sqlWalker->walkSelectStatement($AST);
        } elseif ($AST instanceof AST\UpdateStatement) {
            $this->sqlStatements = $sqlWalker->walkUpdateStatement($AST);
        } elseif ($AST instanceof AST\DeleteStatement) {
            $this->sqlStatements = $sqlWalker->walkDeleteStatement($AST);
        }
    }

    public function finalizeAndExecute(Query $query, Connection $connection, array $params, array $types)
    {
        $platform = $connection->getDatabasePlatform();
        $lockMode = $query->getHint(Query::HINT_LOCK_MODE) ?: LockMode::NONE;

        $this->sqlStatements = $platform->modifyLimitQuery(
            $this->sqlStatements,
            $query->getFirstResult(),
            $query->getMaxResults()
        );

        if ($lockMode === LockMode::PESSIMISTIC_READ) {
            $this->sqlStatements = $this->sqlStatements . ' ' . $this->getReadLockSQL($platform);
        } elseif ($lockMode === LockMode::PESSIMISTIC_WRITE) {
            $this->sqlStatements = $this->sqlStatements . ' ' . $this->getWriteLockSQL($platform);
        } elseif ($lockMode === LockMode::OPTIMISTIC) {
            foreach ($this->selectedClasses as $selectedClass) {
                if (!$selectedClass['class']->isVersioned) {
                    throw OptimisticLockException::lockFailed($selectedClass['class']->name);
                }
            }
        }

        return parent::finalizeAndExecute($query, $connection, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Connection $conn, array $params, array $types): Result
    {
        if ($conn instanceof PrimaryReadReplicaConnection) {
            $conn->ensureConnectedToPrimary();
        }
        return $conn->executeQuery($this->sqlStatements, $params, $types, $this->queryCacheProfile);
    }
}
