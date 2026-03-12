<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

/**
 * Expression class for building DQL select statements.
 *
 * @link    www.doctrine-project.org
 */
class Select extends Base
{
    /** @var string */
    protected $preSeparator = '';

    /** @var string */
    protected $postSeparator = '';

    /** @var list<class-string<Stringable>> */
    protected $allowedClasses = [Func::class];

    /** @phpstan-var list<string|Func> */
    protected $parts = [];

    /** @phpstan-return list<string|Func> */
    public function getParts()
    {
        return $this->parts;
    }
}
