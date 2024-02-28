<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\IdHashing;

/**
 * Turn an array of identifier values into a hash that can be used as a key in an array.
 *
 * This is required for various lookups in the UnitOfWork and adjecent APIs.
 */
interface IdHashing
{
    public function getIdHashByIdentifier(array $identifier): string;
}
