<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL select statements.
 */
class Select extends Base
{
    /** @var string */
    protected $preSeparator = '';

    /** @var string */
    protected $postSeparator = '';

    /** @var string[] */
    protected $allowedClasses = [Func::class];

    /**
     * @return mixed[]
     */
    public function getParts()
    {
        return $this->parts;
    }
}
