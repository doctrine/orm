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
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/10547',
            'The %s class is deprecated and will be removed in ORM 3.0',
            self::class
        );

        $this->hash  = $hash;
        $this->value = $value;
    }
}
