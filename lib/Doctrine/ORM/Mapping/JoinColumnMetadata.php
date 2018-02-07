<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use function strtoupper;

class JoinColumnMetadata extends ColumnMetadata
{
    /** @var string|null */
    protected $referencedColumnName;

    /** @var string|null */
    protected $aliasedName;

    /** @var bool */
    protected $nullable = true;

    /** @var string */
    protected $onDelete = '';

    public function getReferencedColumnName() : ?string
    {
        return $this->referencedColumnName;
    }

    public function setReferencedColumnName(string $referencedColumnName) : void
    {
        $this->referencedColumnName = $referencedColumnName;
    }

    public function getAliasedName() : ?string
    {
        return $this->aliasedName;
    }

    public function setAliasedName(string $aliasedName) : void
    {
        $this->aliasedName = $aliasedName;
    }

    public function getOnDelete() : string
    {
        return $this->onDelete;
    }

    public function setOnDelete(string $onDelete) : void
    {
        $this->onDelete = strtoupper($onDelete);
    }

    public function isOnDeleteCascade() : bool
    {
        return $this->onDelete === 'CASCADE';
    }
}
