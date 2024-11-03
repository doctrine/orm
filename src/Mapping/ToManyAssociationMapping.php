<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

interface ToManyAssociationMapping
{
    /** @phpstan-assert-if-true string $this->indexBy() */
    public function isIndexed(): bool;

    public function indexBy(): string;

    /** @return array<string, 'asc'|'desc'> */
    public function orderBy(): array;
}
