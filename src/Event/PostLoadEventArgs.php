<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\LazyLoadingKind;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/** @extends LifecycleEventArgs<EntityManagerInterface> */
final class PostLoadEventArgs extends LifecycleEventArgs
{
    public function __construct(
        object $entity,
        EntityManagerInterface $em,
        private readonly LazyLoadingKind|null $lazyLoadKind = null,
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
     */
    public function getLazyLoadKind(): LazyLoadingKind|null
    {
        return $this->lazyLoadKind;
    }
}
