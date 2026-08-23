<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

/**
 * Provides an API for resolving the DBAL type name of a Node.
 */
interface ExpressionWithReturnType
{
    /**
     * Returns the DBAL type name (see {@see \Doctrine\DBAL\Types\Types}) of
     * the value produced by this expression.
     */
    public function getReturnTypeName(): string;
}
