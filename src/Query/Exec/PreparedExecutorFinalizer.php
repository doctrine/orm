<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\ORM\Query;

/**
 * PreparedExecutorFinalizer is a wrapper for the SQL finalization
 * phase that does nothing - it is constructed with the sql executor
 * already.
 */
final class PreparedExecutorFinalizer implements SqlFinalizer
{
    /** @var AbstractSqlExecutor */
    private $executor;

    public function __construct(AbstractSqlExecutor $exeutor)
    {
        $this->executor = $exeutor;
    }

    public function createExecutor(Query $query): AbstractSqlExecutor
    {
        return $this->executor;
    }
}
