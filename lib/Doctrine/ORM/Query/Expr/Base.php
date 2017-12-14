<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

/**
 * Abstract base Expr class for building DQL parts.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
abstract class Base
{
    /**
     * @var string
     */
    protected $preSeparator = '(';

    /**
     * @var string
     */
    protected $separator = ', ';

    /**
     * @var string
     */
    protected $postSeparator = ')';

    /**
     * @var array
     */
    protected $allowedClasses = [];

    /**
     * @var array
     */
    protected $parts = [];

    /**
     * @param array $args
     */
    public function __construct($args = [])
    {
        $this->addMultiple($args);
    }

    /**
     * @param array $args
     *
     * @return Base
     */
    public function addMultiple($args = [])
    {
        foreach ((array) $args as $arg) {
            $this->add($arg);
        }

        return $this;
    }

    /**
     * @param mixed $arg
     *
     * @return Base
     *
     * @throws \InvalidArgumentException
     */
    public function add($arg)
    {
        if ( $arg !== null && (!$arg instanceof self || $arg->count() > 0) ) {
            // If we decide to keep Expr\Base instances, we can use this check
            if ( ! is_string($arg)) {
                $class = get_class($arg);

                if ( ! in_array($class, $this->allowedClasses)) {
                    throw new \InvalidArgumentException("Expression of type '$class' not allowed in this context.");
                }
            }

            $this->parts[] = $arg;
        }

        return $this;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return \count($this->parts);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
