<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Query\SqlWalker;

/**
 * InExpression ::= ArithmeticExpression ["NOT"] "IN" "(" (Literal {"," Literal}* | Subselect) ")"
 *
 * @deprecated Use {@see InListExpression} or {@see InSubselectExpression} instead.
 */
class InExpression extends Node
{
    /** @var bool */
    public $not;

    /** @var mixed[] */
    public $literals = [];

    /** @var Subselect|null */
    public $subselect;

    /** @param ArithmeticExpression $expression */
    public function __construct(public $expression)
    {
        if (! $this instanceof InListExpression && ! $this instanceof InSubselectExpression) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/10267',
                '%s is deprecated, use %s or %s instead.',
                self::class,
                InListExpression::class,
                InSubselectExpression::class,
            );
        }
    }

    public function dispatch(SqlWalker $walker): string
    {
        // We still call the deprecated method in order to not break existing custom SQL walkers.
        return $walker->walkInExpression($this);
    }
}
