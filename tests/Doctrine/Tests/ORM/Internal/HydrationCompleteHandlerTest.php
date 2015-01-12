<?php
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

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\HydrationCompleteHandler;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * Tests for {@see \Doctrine\ORM\Internal\HydrationCompleteHandler}
 *
 * @covers \Doctrine\ORM\Internal\HydrationCompleteHandler
 */
class HydrationCompleteHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\Event\ListenersInvoker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $listenersInvoker;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var HydrationCompleteHandler
     */
    private $handler;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->listenersInvoker = $this->getMock('Doctrine\ORM\Event\ListenersInvoker', array(), array(), '', false);
        $this->entityManager    = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->handler          = new HydrationCompleteHandler($this->listenersInvoker, $this->entityManager);
    }

    public function testDefersPostLoadOfEntity()
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata      = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $entity        = new stdClass();
        $entityManager = $this->entityManager;

        $this
            ->listenersInvoker
            ->expects($this->any())
            ->method('getSubscribedSystems')
            ->with($metadata)
            ->will($this->returnValue(ListenersInvoker::INVOKE_LISTENERS));

        $this->handler->deferPostLoadInvoking($metadata, $entity);

        $this
            ->listenersInvoker
            ->expects($this->once())
            ->method('invoke')
            ->with(
                $metadata,
                Events::postLoad,
                $entity,
                $this->callback(function (LifecycleEventArgs $args) use ($entityManager, $entity) {
                    return $entity === $args->getEntity() && $entityManager === $args->getObjectManager();
                }),
                ListenersInvoker::INVOKE_LISTENERS
            );

        $this->handler->hydrationComplete();
    }

    public function testSkipsDeferredPostLoadOfMetadataWithNoInvokedListeners()
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $metadata      = $this->getMock('Doctrine\ORM\Mapping\ClassMetadata', array(), array(), '', false);
        $entity        = new stdClass();

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
}
