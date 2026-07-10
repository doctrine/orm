<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\KMS;

use SensitiveParameter;

/**
 * Value-object to store a key and its id.
 */
final class Key
{
    public function __construct(
        public readonly int|string $id,
        #[SensitiveParameter]
        public readonly string $key,
    ) {
    }
}
