<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Interface TableOwner
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
interface TableOwner
{
    /**
     * Sets the owner table metadata.
     *
     * @param TableMetadata $table
     *
     * @return void
     */
    public function setTable(TableMetadata $table);
}
