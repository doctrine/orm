<?php

namespace Doctrine\ORM\Query\AST;

use Doctrine\ORM\Query\QueryException;

class ASTException extends QueryException
{
    public static function noDispatchForNode($node)
    {
        return new self("Double-dispatch for node " . get_class($node) . " is not supported.");
    }
}