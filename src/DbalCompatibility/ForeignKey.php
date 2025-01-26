<?php

declare(strict_types=1);

namespace Doctrine\ORM\DbalCompatibility;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;

use function method_exists;

/** @internal */
final readonly class ForeignKey
{
    public function __construct(private ForeignKeyConstraint $foreignKey)
    {
    }

    /** @return array<string> */
    public function getReferencingColumns(AbstractPlatform $platform): array
    {
        if (! method_exists($this->foreignKey, 'getReferencingColumns')) {
            return $this->foreignKey->getLocalColumns();
        }

        $namesAsStrings = [];

        foreach ($this->foreignKey->getReferencingColumnNames() as $name) {
            $namesAsStrings[] = $name->toSQL($platform);
        }

        return $namesAsStrings;
    }

    /** @return array<string> */
    public function getReferencedColumns(AbstractPlatform $platform): array
    {
        if (! method_exists($this->foreignKey, 'getReferencedColumns')) {
            return $this->foreignKey->getForeignColumns();
        }

        $namesAsStrings = [];

        foreach ($this->foreignKey->getReferencedColumnNames() as $name) {
            $namesAsStrings[] = $name->toSQL($platform);
        }

        return $namesAsStrings;
    }

    public function getReferencedTableName(AbstractPlatform $platform): string
    {
        if (! method_exists($this->foreignKey, 'getReferencedTableName')) {
            return $this->foreignKey->getForeignTableName();
        }

        return $this->foreignKey->getReferencedTableName()->toSQL($platform);
    }
}
