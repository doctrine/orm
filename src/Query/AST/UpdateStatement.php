<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

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

    /** @param UpdateClause $updateClause */
    public function __construct($updateClause)
    {
        $this->updateClause = $updateClause;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($sqlWalker)
    {
        return $sqlWalker->walkUpdateStatement($this);
    }
}
