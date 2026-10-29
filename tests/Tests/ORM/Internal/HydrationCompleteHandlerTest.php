<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\HydrationCompleteHandler;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function in_array;

/**
 * Tests for {@see \Doctrine\ORM\Internal\HydrationCompleteHandler}
 */
#[CoversClass(HydrationCompleteHandler::class)]
class HydrationCompleteHandlerTest extends TestCase
{
    private ListenersInvoker&MockObject $listenersInvoker;
    private EntityManagerInterface&Stub $entityManager;
    private UnitOfWork&Stub $unitOfWork;
    private HydrationCompleteHandler $handler;

    protected function setUp(): void
    {
        $this->listenersInvoker = $this->createMock(ListenersInvoker::class);
        $this->entityManager    = $this->createStub(EntityManagerInterface::class);
        $this->unitOfWork       = $this->createStub(UnitOfWork::class);
        $this->handler          = new HydrationCompleteHandler($this->listenersInvoker, $this->entityManager, $this->unitOfWork);
    }

    #[DataProvider('invocationFlagProvider')]
    public function testDefersPostLoadOfEntity(int $listenersFlag): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity        = new stdClass();
        $entityManager = $this->entityManager;

        $this
            ->listenersInvoker
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->willReturn($listenersFlag);

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects(self::once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                self::callback(static fn (LifecycleEventArgs $args) => $entity === $args->getObject() && $entityManager === $args->getObjectManager()),
                $listenersFlag,
            );

        $this->handler->hydrationComplete();
    }

    #[DataProvider('invocationFlagProvider')]
    public function testDefersPostLoadOfEntityOnlyOnce(int $listenersFlag): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this
            ->listenersInvoker
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->willReturn($listenersFlag);

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this->listenersInvoker->expects(self::once())->method('invoke');

        $this->handler->hydrationComplete();
        $this->handler->hydrationComplete();
    }

    #[DataProvider('invocationFlagProvider')]
    public function testDefersMultiplePostLoadOfEntity(int $listenersFlag): void
    {
        $metadata1     = $this->createStub(ClassMetadata::class);
        $metadata2     = $this->createStub(ClassMetadata::class);
        $entity1       = new stdClass();
        $entity2       = new stdClass();
        $entityManager = $this->entityManager;

        $this
            ->listenersInvoker
            ->method('getSubscribedSystems')
            ->with(self::logicalOr($metadata1, $metadata2))
            ->willReturn($listenersFlag);

        $this->handler->deferPostLoadInvoking($metadata1, $entity1);
        $this->handler->deferPostLoadInvoking($metadata2, $entity2);

        $this
            ->listenersInvoker
            ->expects(self::exactly(2))
            ->method('invoke')
            ->with(
                self::logicalOr($metadata1, $metadata2),
                Events::postLoad,
                self::logicalOr($entity1, $entity2),
                self::callback(static fn (LifecycleEventArgs $args) => in_array($args->getObject(), [$entity1, $entity2], true)
                    && $entityManager === $args->getObjectManager()),
                $listenersFlag,
            );

        $this->handler->hydrationComplete();
    }

    public function testSkipsDeferredPostLoadOfMetadataWithNoInvokedListeners(): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this
            ->listenersInvoker
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->willReturn(ListenersInvoker::INVOKE_NONE);

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this->listenersInvoker->expects(self::never())->method('invoke');

        $this->handler->hydrationComplete();
    }

    public function testPassesLazyLoadKindCapturedWhenDeferring(): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this->listenersInvoker->method('getSubscribedSystems')->willReturn(ListenersInvoker::INVOKE_LISTENERS);
        $this->unitOfWork->method('getLazyLoadingKind')->willReturn('collection');

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects(self::once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                self::callback(
                    static fn (LifecycleEventArgs $args) => $args instanceof PostLoadEventArgs
                        && $args->isLazyLoad()
                        && $args->getLazyLoadKind() === 'collection',
                ),
                ListenersInvoker::INVOKE_LISTENERS,
            );

        $this->handler->hydrationComplete();
    }

    public function testDeferredPostLoadIsNotALazyLoadByDefault(): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this->listenersInvoker->method('getSubscribedSystems')->willReturn(ListenersInvoker::INVOKE_LISTENERS);

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects(self::once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                self::callback(
                    static fn (LifecycleEventArgs $args) => $args instanceof PostLoadEventArgs
                        && ! $args->isLazyLoad()
                        && $args->getLazyLoadKind() === null,
                ),
                ListenersInvoker::INVOKE_LISTENERS,
            );

        $this->handler->hydrationComplete();
    }

    public function testLazyLoadKindIsCapturedWhenDeferringNotWhenDispatching(): void
    {
        $metadata = $this->createStub(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this->listenersInvoker->method('getSubscribedSystems')->willReturn(ListenersInvoker::INVOKE_LISTENERS);
        $this
            ->unitOfWork
            ->method('getLazyLoadingKind')
            ->willReturnOnConsecutiveCalls('collection', 'to_one');

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects(self::once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                self::callback(
                    static fn (LifecycleEventArgs $args) => $args instanceof PostLoadEventArgs
                        && $args->getLazyLoadKind() === 'collection',
                ),
                ListenersInvoker::INVOKE_LISTENERS,
            );

        $this->handler->hydrationComplete();
    }

    /** @phpstan-return list<array{int}> */
    public static function invocationFlagProvider(): array
    {
        return [
            [ListenersInvoker::INVOKE_LISTENERS],
            [ListenersInvoker::INVOKE_CALLBACKS],
            [ListenersInvoker::INVOKE_MANAGER],
            [ListenersInvoker::INVOKE_LISTENERS | ListenersInvoker::INVOKE_CALLBACKS],
            [ListenersInvoker::INVOKE_LISTENERS | ListenersInvoker::INVOKE_MANAGER],
            [ListenersInvoker::INVOKE_LISTENERS | ListenersInvoker::INVOKE_CALLBACKS | ListenersInvoker::INVOKE_MANAGER],
        ];
    }
}
