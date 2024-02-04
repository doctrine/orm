<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Exception;

final class MetadataCacheNotConfigured extends CacheException
{
    public static function create(): self
    {
        return new self('Class Metadata Cache is not configured.');
    }
}
