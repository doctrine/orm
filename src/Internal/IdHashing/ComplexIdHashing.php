<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\IdHashing;

use BackedEnum;

use function array_map;
use function implode;

class ComplexIdHashing implements IdHashing
{
    public function getIdHashByIdentifier(array $identifier): string
    {
        return implode(
            ' ',
            array_map(
                static function ($value) {
                    if ($value instanceof BackedEnum) {
                        return $value->value;
                    }

                    return $value;
                },
                $identifier
            )
        );
    }
}
