<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class CacheUsage
 */
final class CacheUsage
{
    /**
     * ReadOnly cache can do reads, inserts and deletes, cannot perform updates or employ any locks,
     */
    public const READ_ONLY = 'READ_ONLY';

    /**
     * Read Write Attempts to lock the entity before update/delete.
     */
    public const READ_WRITE = 'READ_WRITE';

    /**
     * Nonstrict Read Write Cache doesn’t employ any locks but can do inserts, update and deletes.
     */
    public const NONSTRICT_READ_WRITE = 'NONSTRICT_READ_WRITE';

    /**
     * Will break upon instantiation.
     */
    private function __construct()
    {
    }
}
