<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use InvalidArgumentException;
use Stringable;

use function count;
use function get_debug_type;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Abstract base Expr class for building DQL parts.
 *
 * @link    www.doctrine-project.org
 */
abstract class Base implements Stringable
{
    protected string $preSeparator  = '(';
    protected string $separator     = ', ';
    protected string $postSeparator = ')';

    /** @var list<class-string> */
    protected array $allowedClasses = [];

    /** @var list<string|Stringable> */
    protected array $parts = [];

    public function __construct(mixed $args = [])
    {
        $this->addMultiple($args);
    }

    /**
     * @param string[]|object[]|string|object $args
     * @psalm-param list<string|object>|string|object $args
     *
     * @return $this
     */
    public function addMultiple(array|string|object $args = []): static
    {
        foreach ((array) $args as $arg) {
            $this->add($arg);
        }

        return $this;
    }

    /**
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $arg): static
    {
        if ($arg !== null && (! $arg instanceof self || $arg->count() > 0)) {
            // If we decide to keep Expr\Base instances, we can use this check
            if (! is_string($arg) && ! in_array($arg::class, $this->allowedClasses, true)) {
                throw new InvalidArgumentException(sprintf(
                    "Expression of type '%s' not allowed in this context.",
                    get_debug_type($arg),
                ));
            }

            $this->parts[] = $arg;
        }

        return $this;
    }

    /** @psalm-return 0|positive-int */
    public function count(): int
    {
        return count($this->parts);
    }

    public function __toString(): string
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
