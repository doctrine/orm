<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

final class Between
{
    public const NOT_BETWEEN = 'NOT BETWEEN';

    public const BETWEEN = 'BETWEEN';

    /**
     * @var string
     */
    private $operator;

    /**
     * @var int|string
     */
    private $key;

    /**
     * @var int|string
     */
    private $min;

    /**
     * @var int|string
     */
    private $max;

    /**
     * @param string     $operator
     * @param int|string $key
     * @param int|string $min
     * @param int|string $max
     */
    public function __construct(string $operator, $key, $min, $max)
    {
        $this->operator = $operator;
        $this->key = $key;
        $this->min = $min;
        $this->max = $max;
    }

    public function __toString() : string
    {
        return sprintf('%s %s %s AND %s', $this->key, $this->operator, $this->min, $this->max);
    }
}
