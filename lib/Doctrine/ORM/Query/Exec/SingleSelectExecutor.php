<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Executor that executes the SQL statement for simple DQL SELECT statements.
 *
 * @deprecated This class will be removed in 3.0
 *
 * @link        www.doctrine-project.org
 */
class SingleSelectExecutor extends AbstractSqlExecutor
{
    public function __construct(SelectStatement $AST, SqlWalker $sqlWalker)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/xxx',
            'The %s class will be removed in Doctrine ORM 3.0',
            self::class
        );

        parent::__construct();

        $this->sqlStatements = $sqlWalker->walkSelectStatement($AST);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function execute(Connection $conn, array $params, array $types)
    {
        return $conn->executeQuery($this->sqlStatements, $params, $types, $this->queryCacheProfile);
    }
}
