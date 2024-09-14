<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\TopologicalSort;

use RuntimeException;

use function array_unshift;

class CycleDetectedException extends RuntimeException
{
    /** @var list<object> */
    private $cycle;

    /** @var object */
    private $startNode;

    /**
     * Do we have the complete cycle collected?
     *
     * @var bool
     */
    private $cycleCollected = false;

    /** @param object $startNode */
    public function __construct($startNode)
    {
        parent::__construct('A cycle has been detected, so a topological sort is not possible. The getCycle() method provides the list of nodes that form the cycle.');

        $this->startNode = $startNode;
        $this->cycle     = [$startNode];
    }

    /** @return list<object> */
    public function getCycle(): array
    {
        return $this->cycle;
    }

    /** @param object $node */
    public function addToCycle($node): void
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
