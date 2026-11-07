<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function bin2hex;
use function random_bytes;

/** @internal */
final class CollectionCacheVersionEntry implements CacheEntry
{
    public readonly string $version;

    public function __construct(string|null $version = null)
    {
        $this->version = $version ?? bin2hex(random_bytes(16));
    }

    /** @param array{version: string} $values */
    public static function __set_state(array $values): self
    {
        return new self($values['version']);
    }
}
