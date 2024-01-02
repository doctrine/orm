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
class NullSqlWalker extends SqlWalker
{
    public function walkSelectStatement(AST\SelectStatement $AST): string
    {
        return '';
    }

    public function walkUpdateStatement(AST\UpdateStatement $AST): string
    {
        return '';
    }

    public function walkDeleteStatement(AST\DeleteStatement $AST): string
    {
        return '';
    }

    public function getExecutor($AST): AbstractSqlExecutor
    {
        return new class extends AbstractSqlExecutor {
            public function execute(Connection $conn, array $params, array $types): int
            {
                return 0;
            }
        };
    }
}
