<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

interface TableOwner
{
    /**
     * Sets the owner table metadata.
     */
    public function setTable(TableMetadata $table) : void;
}
