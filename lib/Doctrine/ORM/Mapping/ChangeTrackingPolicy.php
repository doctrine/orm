<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

final class ChangeTrackingPolicy
{
    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const DEFERRED_IMPLICIT = 'DEFERRED_IMPLICIT';

    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const DEFERRED_EXPLICIT = 'DEFERRED_EXPLICIT';

    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications when their
     * properties change. Such entity classes must implement the <tt>NotifyPropertyChanged</tt>
     * interface.
     */
    const NOTIFY = 'NOTIFY';

    /**
     * Will break upon instantiation.
     *
     */
    private function __construct()
    {
    }
}
