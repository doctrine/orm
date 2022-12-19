<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\CommitOrder;

/** @internal */
final class Edge
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly int $weight,
    ) {
    }
}
