<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister;

use Doctrine\ORM\Cache\Region;

/**
 * Interface for persister that support second level cache.
 */
interface CachedPersister
{
    /**
     * Perform whatever processing is encapsulated here after completion of the transaction.
     */
    public function afterTransactionComplete(): void;

    /**
     * Perform whatever processing is encapsulated here after completion of the rolled-back.
     */
    public function afterTransactionRolledBack(): void;

    public function getCacheRegion(): Region;
}
