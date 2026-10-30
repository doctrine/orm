<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\LazyLoadingKind;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

/**
 * Class, which can handle completion of hydration cycle and produce some of tasks.
 * In current implementation triggers deferred postLoad event.
 */
final class HydrationCompleteHandler
{
    /** @var list<array{ClassMetadata<object>, int<0, 7>, object, LazyLoadingKind|null}> */
    private array $deferredPostLoadInvocations = [];

    public function __construct(
        private readonly ListenersInvoker $listenersInvoker,
        private readonly EntityManagerInterface $em,
        private readonly UnitOfWork $uow,
    ) {
    }

    /**
     * Method schedules invoking of postLoad entity to the very end of current hydration cycle.
     */
    public function deferPostLoadInvoking(ClassMetadata $class, object $entity): void
    {
        $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::postLoad);

        if ($invoke === ListenersInvoker::INVOKE_NONE) {
            return;
        }

        // Capture the lazy loading kind now: a nested lazy load dispatched before
        // hydrationComplete() must not overwrite the kind of already queued entities.
        $lazyLoadKind = $this->uow->getLazyLoadingKind();

        $this->deferredPostLoadInvocations[] = [$class, $invoke, $entity, $lazyLoadKind];
    }

    /**
     * This method should be called after any hydration cycle completed.
     *
     * Method fires all deferred invocations of postLoad events
     */
    public function hydrationComplete(): void
    {
        $toInvoke                          = $this->deferredPostLoadInvocations;
        $this->deferredPostLoadInvocations = [];

        foreach ($toInvoke as $classAndEntity) {
            [$class, $invoke, $entity, $lazyLoadKind] = $classAndEntity;

            $this->listenersInvoker->invoke(
                $class,
                Events::postLoad,
                $entity,
                new PostLoadEventArgs($entity, $this->em, $lazyLoadKind),
                $invoke,
            );
        }
    }
}
