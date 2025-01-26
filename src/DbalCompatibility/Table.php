<?php

declare(strict_types=1);

namespace Doctrine\ORM\DbalCompatibility;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

use function array_map;

/** @internal */
final readonly class Table
{
    public function __construct(private DoctrineTable $table)
    {
    }

    /** @return array<string, ForeignKey> */
    public function getForeignKeys(): array
    {
        return array_map(
            static fn (ForeignKeyConstraint $foreignKey) => new ForeignKey($foreignKey),
            $this->table->getForeignKeys(),
        );
    }
}
