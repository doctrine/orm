<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/** @extends LifecycleEventArgs<EntityManagerInterface> */
final class PostLoadEventArgs extends LifecycleEventArgs
{
    /** @param 'to_one'|'collection'|null $lazyLoadKind */
    public function __construct(
        object $entity,
        EntityManagerInterface $em,
        private readonly string|null $lazyLoadKind = null,
    ) {
        parent::__construct($entity, $em);
    }

    /**
     * Whether this entity was loaded because the ORM was initializing a lazy
     * proxy or a lazy collection.
     */
    public function isLazyLoad(): bool
    {
        return $this->lazyLoadKind !== null;
    }

    /**
     * Kind of the innermost lazy loading operation that caused this entity to
     * be loaded, or null for a regular query.
     *
     * @return 'to_one'|'collection'|null
     */
    public function getLazyLoadKind(): string|null
    {
        return $this->lazyLoadKind;
    }
}
