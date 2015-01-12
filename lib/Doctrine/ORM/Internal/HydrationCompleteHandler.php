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

namespace Doctrine\ORM\Internal;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

/**
 * Class, which can handle completion of hydration cycle and produce some of tasks.
 * In current implementation triggers deferred postLoad event.
 *
 * TODO Move deferred eager loading here
 *
 * @author Artur Eshenbrener <strate@yandex.ru>
 * @since 2.5
 */
final class HydrationCompleteHandler
{
    /** @var \Doctrine\ORM\UnitOfWork */
    private $uow;

    /** @var \Doctrine\ORM\Event\ListenersInvoker */
    private $listenersInvoker;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var array */
    private $deferredPostLoadInvocations = array();

    /**
     * Constructor for this object
     *
     * @param UnitOfWork $uow
     * @param \Doctrine\ORM\Event\ListenersInvoker $listenersInvoker
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(UnitOfWork $uow, ListenersInvoker $listenersInvoker, EntityManagerInterface $em)
    {
        $this->uow = $uow;
        $this->listenersInvoker = $listenersInvoker;
        $this->em = $em;
    }

    /**
     * Method schedules invoking of postLoad entity to the very end of current hydration cycle.
     *
     * @param ClassMetadata $class
     * @param object $entity
     */
    public function deferPostLoadInvoking(ClassMetadata $class, $entity)
    {
        $this->deferredPostLoadInvocations[] = array($class, $entity);
    }

    /**
     * This method should me called after any hydration cycle completed.
     */
    public function hydrationComplete()
    {
        $this->invokeAllDeferredPostLoadEvents();
    }

    /**
     * Method fires all deferred invocations of postLoad events
     */
    private function invokeAllDeferredPostLoadEvents()
    {
        $toInvoke = $this->deferredPostLoadInvocations;
        $this->deferredPostLoadInvocations = array();
        foreach ($toInvoke as $classAndEntity) {
            list($class, $entity) = $classAndEntity;

            $invoke = $this->listenersInvoker->getSubscribedSystems($class, Events::postLoad);

            if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                $this->listenersInvoker->invoke($class, Events::postLoad, $entity, new LifecycleEventArgs($entity, $this->em), $invoke);
            }
        }
    }
}
