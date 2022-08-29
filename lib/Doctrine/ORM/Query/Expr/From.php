<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Expression class for DQL from.
 *
 * @link    www.doctrine-project.org
 */
class From
{
    /**
     * @param class-string $from    The class name.
     * @param string       $alias   The alias of the class.
     * @param string       $indexBy The index for the from.
     */
    public function __construct(protected $from, protected $alias, protected $indexBy = null)
    {
    }

    /** @return class-string */
    public function getFrom()
    {
        return $this->from;
    }

    /** @return string */
    public function getAlias()
    {
        return $this->alias;
    }

    /** @return string|null */
    public function getIndexBy()
    {
        return $this->indexBy;
    }

    /** @return string */
    public function __toString()
    {
        return $this->from . ' ' . $this->alias .
                ($this->indexBy ? ' INDEX BY ' . $this->indexBy : '');
    }
}
