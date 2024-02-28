<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\IdHashing;

use function implode;

class SingleElementIdHashing implements IdHashing
{
    /**
     * @param mixed[] $identifier
     */
    public function getIdHashByIdentifier(array $identifier): string
    {
        return implode(' ', $identifier);
    }
}
