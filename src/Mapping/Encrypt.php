<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Attribute;
use Doctrine\ORM\Encrypt\EncryptQuery;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Encrypt implements MappingAttribute
{
    public function __construct(
        public readonly string|null $cipher = null,
        public readonly string|null $keyProvider = null,
        public readonly EncryptQuery|null $queryType = null,
    ) {
    }
}
