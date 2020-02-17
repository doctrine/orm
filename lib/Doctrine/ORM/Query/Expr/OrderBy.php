<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use function count;
use function implode;

/**
 * Expression class for building DQL Order By parts.
 */
class OrderBy
{
    /** @var string */
    protected $preSeparator = '';

    /** @var string */
    protected $separator = ', ';

    /** @var string */
    protected $postSeparator = '';

    /** @var string[] */
    protected $allowedClasses = [];

    /** @var mixed[] */
    protected $parts = [];

    /**
     * @param string|null $sort
     * @param string|null $order
     */
    public function __construct($sort = null, $order = null)
    {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    /**
     * @param string      $sort
     * @param string|null $order
     */
    public function add($sort, $order = null)
    {
        $order         = ! $order ? 'ASC' : $order;
        $this->parts[] = $sort . ' ' . $order;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @return mixed[]
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
