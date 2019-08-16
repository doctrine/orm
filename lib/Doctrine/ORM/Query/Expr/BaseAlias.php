<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use InvalidArgumentException;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Abstract base Expr class for building DQL parts.
 */
abstract class BaseAlias extends Base
{
    /**
     * @param mixed $args
     *
     * @return Base
     */
    public function addMultiple($args = [])
    {
        foreach ((array) $args as $alias => $arg) {
            if (is_string($alias)) {
                $this->add($arg, $alias);

                continue;
            }
            parent::add($arg);
        }

        return $this;
    }

    /**
     * @param mixed       $arg
     * @param string|null $alias
     *
     * @return Base
     *
     * @throws InvalidArgumentException
     */
    public function add($arg, $alias = null)
    {
        if ($alias !== null && is_array($arg) && (! $arg instanceof self || $arg->count() > 0)) {
            foreach ($arg as $v) {
                parent::add(sprintf('%s.%s', $alias, $v));
            }

            return $this;
        }

        return parent::add($arg);
    }
}
