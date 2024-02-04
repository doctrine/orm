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
 */
interface OutputWalker extends TreeWalker
{
    /** @param AST\DeleteStatement|AST\UpdateStatement|AST\SelectStatement $AST */
    public function getFinalizer($AST): SqlFinalizer;
}
