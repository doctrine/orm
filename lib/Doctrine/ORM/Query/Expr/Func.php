<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

use function implode;

/**
 * Expression class for generating DQL functions.
 *
 * @link    www.doctrine-project.org
 */
class Func implements Stringable
{
    /** @var mixed[] */
    protected array $arguments;

    /**
     * Creates a function, with the given argument.
     *
     * @psalm-param list<mixed>|mixed $arguments
     */
    public function __construct(
        protected string $name,
        mixed $arguments,
    ) {
        $this->arguments = (array) $arguments;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @psalm-return list<mixed> */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function __toString(): string
    {
        return $this->name . '(' . implode(', ', $this->arguments) . ')';
    }
}
