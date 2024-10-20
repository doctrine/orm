<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Query\Exec\SqlFinalizer;

/**
 * Interface for output walkers
 *
 * Output walkers, like tree walkers, can traverse the DQL AST to perform
 * their purpose.
 *
 * The goal of an OutputWalker is to ultimately provide the SqlFinalizer
 * which produces the final, executable SQL statement in a "finalization" phase.
 *
 * It must be possible to use the same SqlFinalizer for Queries with different
 * firstResult/maxResult values. In other words, SQL produced by the
 * output walker should not depend on those values, and any SQL generation/modification
 * specific to them should happen in the finalizer's `\Doctrine\ORM\Query\Exec\SqlFinalizer::createExecutor()`
 * method instead.
 */
interface OutputWalker
{
    public function getFinalizer(AST\DeleteStatement|AST\UpdateStatement|AST\SelectStatement $AST): SqlFinalizer;
}
