<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\TopologicalSort;

use RuntimeException;

use function array_unshift;

class CycleDetectedException extends RuntimeException
{
    /** @var list<object> */
    private array $cycle;

    /**
     * Do we have the complete cycle collected?
     */
    private bool $cycleCollected = false;

    public function __construct(private readonly object $startNode)
    {
        parent::__construct('A cycle has been detected, so a topological sort is not possible. The getCycle() method provides the list of nodes that form the cycle.');

        $this->cycle = [$startNode];
    }

    /** @return list<object> */
    public function getCycle(): array
    {
        return $this->cycle;
    }

    public function addToCycle(object $node): void
    {
        array_unshift($this->cycle, $node);

        if ($node === $this->startNode) {
            $this->cycleCollected = true;
        }
    }

    public function isCycleCollected(): bool
    {
        return $this->cycleCollected;
    }
}
