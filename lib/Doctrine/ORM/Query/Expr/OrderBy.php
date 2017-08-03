<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for building DQL Order By parts.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class OrderBy
{
    /**
     * @var string
     */
    protected $preSeparator = '';

    /**
     * @var string
     */
    protected $separator = ', ';

    /**
     * @var string
     */
    protected $postSeparator = '';

    /**
     * @var array
     */
    protected $allowedClasses = [];

    /**
     * @var array
     */
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
     *
     * @return void
     */
    public function add($sort, $order = null)
    {
        $order = ! $order ? 'ASC' : $order;
        $this->parts[] = $sort . ' '. $order;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * @return array
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
