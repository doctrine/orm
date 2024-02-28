<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\Query\Exec\PreparedExecutorFinalizer;
use Doctrine\ORM\Query\Exec\SingleSelectSqlFinalizer;
use Doctrine\ORM\Query\Exec\SqlFinalizer;

class SqlOutputWalker extends SqlWalker implements OutputWalker
{
    public function getFinalizer($AST): SqlFinalizer
    {
        if (! $AST instanceof AST\SelectStatement) {
            return new PreparedExecutorFinalizer(parent::getExecutor($AST));
        }

        return new SingleSelectSqlFinalizer($this->createSqlForFinalizer($AST));
    }
}
