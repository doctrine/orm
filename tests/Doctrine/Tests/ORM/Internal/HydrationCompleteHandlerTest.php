<?php

declare(strict_types=1);

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\HydrationCompleteHandler;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

use function assert;
use function in_array;

/**
 * Tests for {@see \Doctrine\ORM\Internal\HydrationCompleteHandler}
 *
 * @covers \Doctrine\ORM\Internal\HydrationCompleteHandler
 */
class HydrationCompleteHandlerTest extends TestCase
{
    /** @var ListenersInvoker|MockObject */
    private $listenersInvoker;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var HydrationCompleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->listenersInvoker = $this->createMock(ListenersInvoker::class);
        $this->entityManager    = $this->createMock(EntityManagerInterface::class);
        $this->handler          = new HydrationCompleteHandler($this->listenersInvoker, $this->entityManager);
    }

    /**
     * @dataProvider invocationFlagProvider
     */
    public function testDefersPostLoadOfEntity(int $listenersFlag): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity        = new stdClass();
        $entityManager = $this->entityManager;

        $this
            ->listenersInvoker
            ->expects($this->any())
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->will($this->returnValue($listenersFlag));

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects($this->once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                $this->callback(static function (LifecycleEventArgs $args) use ($entityManager, $entity) {
                    return $entity === $args->getEntity() && $entityManager === $args->getObjectManager();
                }),
                $listenersFlag
            );

        $this->handler->hydrationComplete();
    }

    /**
     * @dataProvider invocationFlagProvider
     */
    public function testDefersPostLoadOfEntityOnlyOnce(int $listenersFlag): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this
            ->listenersInvoker
            ->expects($this->any())
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->will($this->returnValue($listenersFlag));

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this->listenersInvoker->expects($this->once())->method('invoke');

        $this->handler->hydrationComplete();
        $this->handler->hydrationComplete();
    }

    /**
     * @dataProvider invocationFlagProvider
     */
    public function testDefersMultiplePostLoadOfEntity(int $listenersFlag): void
    {
        $metadata1     = $this->createMock(ClassMetadata::class);
        $metadata2     = $this->createMock(ClassMetadata::class);
        $entity1       = new stdClass();
        $entity2       = new stdClass();
        $entityManager = $this->entityManager;

        $this
            ->listenersInvoker
            ->expects($this->any())
            ->method('getSubscribedSystems')
            ->with($this->logicalOr($metadata1, $metadata2))
            ->will($this->returnValue($listenersFlag));

        $this->handler->deferPostLoadInvoking($metadata1, $entity1);
        $this->handler->deferPostLoadInvoking($metadata2, $entity2);

        $this
            ->listenersInvoker
            ->expects($this->exactly(2))
            ->method('invoke')
            ->with(
                $this->logicalOr($metadata1, $metadata2),
                Events::postLoad,
                $this->logicalOr($entity1, $entity2),
                $this->callback(static function (LifecycleEventArgs $args) use ($entityManager, $entity1, $entity2) {
                    return in_array($args->getEntity(), [$entity1, $entity2], true)
                        && $entityManager === $args->getObjectManager();
                }),
                $listenersFlag
            );

        $this->handler->hydrationComplete();
    }

    public function testSkipsDeferredPostLoadOfMetadataWithNoInvokedListeners(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        assert($metadata instanceof ClassMetadata);
        $entity = new stdClass();

        $this
            ->listenersInvoker
            ->expects($this->any())
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->will($this->returnValue(ListenersInvoker::INVOKE_NONE));

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this->listenersInvoker->expects($this->never())->method('invoke');

        $this->handler->hydrationComplete();
    }

    /**
     * @psalm-return list<array{int}>
     */
    public function invocationFlagProvider(): array
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
