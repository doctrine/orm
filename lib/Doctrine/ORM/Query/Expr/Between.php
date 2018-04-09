<?php

namespace Doctrine\ORM\Query\Expr;

class Between
{
    const NOT_BETWEEN = 'NOT BETWEEN';

    const BETWEEN = 'BETWEEN';

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var mixed
     */
    protected $key;

    /**
     * @var int|string
     */
    protected $min;

    /**
     * @var int|string
     */
    protected $max;

    /**
     * Constructor.
     *
     * @param string     $operator
     * @param int|string $key
     * @param int|string $min
     * @param int|string $max
     */
    public function __construct($operator, $key, $min, $max)
    {
        $this->operator = $operator;
        $this->key = $key;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return int|string
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @return int|string
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s %s %s AND %s', $this->key, $this->operator, $this->min, $this->max);
    }
}
