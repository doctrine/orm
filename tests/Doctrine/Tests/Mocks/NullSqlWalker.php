<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\SqlWalker;

/**
 * SqlWalker implementation that does not produce SQL.
 */
final class NullSqlWalker extends SqlWalker
{
    public function walkSelectStatement(AST\SelectStatement $selectStatement): string
    {
        return '';
    }

    public function walkUpdateStatement(AST\UpdateStatement $updateStatement): string
    {
        return '';
    }

    public function walkDeleteStatement(AST\DeleteStatement $deleteStatement): string
    {
        return '';
    }

    public function getExecutor(AST\SelectStatement|AST\UpdateStatement|AST\DeleteStatement $statement): AbstractSqlExecutor
    {
        return new class extends AbstractSqlExecutor {
            public function execute(Connection $conn, array $params, array $types): int
            {
                return 0;
            }
        };
    }
}
