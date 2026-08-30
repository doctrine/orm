<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

/** The kind of lazy load that was attempted. */
enum LazyLoadKind: string
{
    /** An uninitialized entity (a reference/proxy) was about to be loaded. */
    case Entity = 'entity';

    /** An uninitialized collection was about to be loaded entirely. */
    case Collection = 'collection';

    /**
     * An operation on an uninitialized EXTRA_LAZY collection was about to
     * issue its own query (count(), contains(), slice(), …).
     */
    case CollectionQuery = 'collection query';
}
