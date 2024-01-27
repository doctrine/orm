<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\ORM\Query;

/**
 * SqlFinalizers are created by OutputWalkers that traversed the DQL AST.
 * The SqlFinalizer instance can be kept in the query cache and re-used
 * at a later time.
 *
 * Once the SqlFinalizer has been created or retrieved from the query cache,
 * it receives the Query object again in order to yield the AbstractSqlExecutor
 * that will then be used to execute the query.
 *
 * The SqlFinalizer may assume that the DQL that was used to build the AST
 * and run the OutputWalker (which created the SqlFinalizer) is equivalent to
 * the query that will be passed to the createExecutor() method. Potential differences
 * are the parameter values or firstResult/maxResult settings.
 */
interface SqlFinalizer
{
    public function createExecutor(Query $query): AbstractSqlExecutor;
}
