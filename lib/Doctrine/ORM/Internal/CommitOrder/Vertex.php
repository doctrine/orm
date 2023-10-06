<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @internal
 * @deprecated
 */
final class Vertex
{
    public VertexState $state = VertexState::NotVisited;

    /** @var array<string, Edge> */
    public array $dependencyList = [];

    public function __construct(
        public readonly string $hash,
        public readonly ClassMetadata $value,
    ) {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/10547',
            'The %s class is deprecated and will be removed in ORM 3.0',
            self::class,
        );
    }
}
