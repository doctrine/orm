<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL Group By parts.
 */
class GroupBy extends Base
{
    /** @var string */
    protected $preSeparator = '';

    /** @var string */
    protected $postSeparator = '';

    /**
     * @return mixed[]
     */
    public function getParts()
    {
        return $this->parts;
    }
}
