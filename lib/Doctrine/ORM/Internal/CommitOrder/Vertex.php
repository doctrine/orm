<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

use Doctrine\ORM\Mapping\ClassMetadata;

/** @internal */
final class Vertex
{
    public VertexState $state = VertexState::NotVisited;

    /** @var array<string, Edge> */
    public array $dependencyList = [];

    public function __construct(
        public readonly string $hash,
        public readonly ClassMetadata $value,
    ) {
    }
}
