<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\IdHashing;

use function array_key_first;

class SingleElementIdHashing implements IdHashing
{
    public function getIdHashByIdentifier(array $identifier): string
    {
        return implode(' ', $identifier);
    }
}
