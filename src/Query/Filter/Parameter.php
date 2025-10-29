<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;

/** @internal */
final class Parameter
{
    /** @param ParameterType::*|ArrayParameterType::*|string $type */
    public function __construct(
        public readonly mixed $value,
        public readonly ParameterType|ArrayParameterType|int|string $type,
        public readonly bool $isList,
    ) {
    }
}
