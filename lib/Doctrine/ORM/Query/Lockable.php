<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query;

use Doctrine\ORM\TransactionRequiredException;

interface Lockable
{
    /**
     * @var string
     */
    const HINT_LOCK_MODE = 'doctrine.lockMode';

    /**
     * Set the lock mode for this Query.
     *
     * @see \Doctrine\DBAL\LockMode
     *
     * @param int $lockMode
     *
     * @return static
     *
     * @throws TransactionRequiredException
     */
    public function setLockMode($lockMode);

    /**
     * Get the current lock mode for this query.
     *
     * @return int|null The current lock mode of this query or NULL if no specific lock mode is set.
     */
    public function getLockMode();
}
