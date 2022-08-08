<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * UpdateStatement = UpdateClause [WhereClause]
 *
 * @link    www.doctrine-project.org
 */
class UpdateStatement extends Node
{
    /** @var UpdateClause */
    public $updateClause;

    /** @var WhereClause|null */
    public $whereClause;

    /**
     * @param UpdateClause $updateClause
     */
    public function __construct($updateClause)
    {
        $this->updateClause = $updateClause;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkUpdateStatement($this);
    }
}
