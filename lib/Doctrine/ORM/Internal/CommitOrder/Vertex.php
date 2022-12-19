<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

use Doctrine\ORM\Mapping\ClassMetadata;

/** @internal */
final class Vertex
{
    /**
     * @var string
     * @readonly
     */
    public $hash;

    /**
     * @var int
     * @psalm-var VertexState::*
     */
    public $state = VertexState::NOT_VISITED;

    /**
     * @var ClassMetadata
     * @readonly
     */
    public $value;

    /** @var array<string, Edge> */
    public $dependencyList = [];

    public function __construct(string $hash, ClassMetadata $value)
    {
        $this->hash  = $hash;
        $this->value = $value;
    }
}
