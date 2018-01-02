<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache\Persister;

/**
 * Interface for persister that support second level cache.
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface CachedPersister
{
    /**
     * Perform whatever processing is encapsulated here after completion of the transaction.
     */
    public function afterTransactionComplete();

    /**
     * Perform whatever processing is encapsulated here after completion of the rolled-back.
     */
    public function afterTransactionRolledBack();

    /**
     * Gets the The region access.
     *
     * @return \Doctrine\ORM\Cache\Region
     */
    public function getCacheRegion();
}
