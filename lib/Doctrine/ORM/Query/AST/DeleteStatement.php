<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\SqlWalker;

/**
 * DeleteStatement = DeleteClause [WhereClause]
 *
 * @link    www.doctrine-project.org
 */
class DeleteStatement extends Node
{
    /** @var DeleteClause */
    public $deleteClause;

    /** @var WhereClause|null */
    public $whereClause;

    /**
     * @param DeleteClause $deleteClause
     */
    public function __construct($deleteClause)
    {
        $this->deleteClause = $deleteClause;
    }

    public function dispatch(SqlWalker $walker): string
    {
        return $walker->walkDeleteStatement($this);
    }
}
