<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class JoinColumnMetadata extends ColumnMetadata
{
    /** @var string */
    protected $referencedColumnName;

    /** @var string */
    protected $aliasedName;

    /** @var boolean */
    protected $nullable = true;

    /** @var string */
    protected $onDelete = '';

    /**
     * @return string
     */
    public function getReferencedColumnName()
    {
        return $this->referencedColumnName;
    }

    /**
     * @param string $referencedColumnName
     */
    public function setReferencedColumnName(string $referencedColumnName)
    {
        $this->referencedColumnName = $referencedColumnName;
    }

    /**
     * @return string
     */
    public function getAliasedName()
    {
        return $this->aliasedName;
    }

    /**
     * @param string $aliasedName
     */
    public function setAliasedName(string $aliasedName)
    {
        $this->aliasedName = $aliasedName;
    }

    /**
     * @return string
     */
    public function getOnDelete()
    {
        return $this->onDelete;
    }

    /**
     * @param string $onDelete
     */
    public function setOnDelete(string $onDelete)
    {
        $this->onDelete = strtoupper($onDelete);
    }

    /**
     * @return bool
     */
    public function isOnDeleteCascade()
    {
        return $this->onDelete === 'CASCADE';
    }
}
