<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Expr;

use Stringable;

use function count;
use function implode;

/**
 * Expression class for building DQL Order By parts.
 *
 * @link    www.doctrine-project.org
 */
class OrderBy implements Stringable
{
    protected string $preSeparator  = '';
    protected string $separator     = ', ';
    protected string $postSeparator = '';

    /** @var string[] */
    protected array $allowedClasses = [];

    /** @phpstan-var list<string> */
    protected array $parts = [];

    public function __construct(
        string|null $sort = null,
        string|null $order = null,
    ) {
        if ($sort) {
            $this->add($sort, $order);
        }
    }

    public function add(string $sort, string|null $order = null): void
    {
        $order         = ! $order ? 'ASC' : $order;
        $this->parts[] = $sort . ' ' . $order;
    }

    /** @phpstan-return 0|positive-int */
    public function count(): int
    {
        return count($this->parts);
    }

    /** @phpstan-return list<string> */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function __toString(): string
    {
        return $this->preSeparator . implode($this->separator, $this->parts) . $this->postSeparator;
    }
}
