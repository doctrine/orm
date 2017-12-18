<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

final class MetadataCacheNotConfigured extends \Exception implements CacheException
{
    public static function create() : self
    {
        return new self('Class Metadata Cache is not configured.');
    }
}
