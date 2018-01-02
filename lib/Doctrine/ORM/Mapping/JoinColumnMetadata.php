<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class JoinColumnMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class JoinColumnMetadata extends ColumnMetadata
{
    /** @var string|null */
    protected $referencedColumnName;

    /** @var string|null */
    protected $aliasedName;

    /** @var boolean */
    protected $nullable = true;

    /** @var string */
    protected $onDelete = '';

    /**
     * @return string|null
     */
    public function getReferencedColumnName() : ?string
    {
        return $this->referencedColumnName;
    }

    /**
     * @param string $referencedColumnName
     */
    public function setReferencedColumnName(string $referencedColumnName) : void
    {
        $this->referencedColumnName = $referencedColumnName;
    }

    /**
     * @return string|null
     */
    public function getAliasedName() : ?string
    {
        return $this->aliasedName;
    }

    /**
     * @param string $aliasedName
     */
    public function setAliasedName(string $aliasedName) : void
    {
        $this->aliasedName = $aliasedName;
    }

    /**
     * @return string
     */
    public function getOnDelete() : string
    {
        return $this->onDelete;
    }

    /**
     * @param string $onDelete
     */
    public function setOnDelete(string $onDelete) : void
    {
        $this->onDelete = strtoupper($onDelete);
    }

    /**
     * @return bool
     */
    public function isOnDeleteCascade() : bool
    {
        return $this->onDelete === 'CASCADE';
    }
}
