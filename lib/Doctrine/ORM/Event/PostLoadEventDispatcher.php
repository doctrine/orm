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

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Query;

/**
 * Dispatcher for postLoad event on entities used during object hydration.
 *
 * @author  Lukasz Cybula <lukasz@fsi.pl>
 * @since   2.4
 */
class PostLoadEventDispatcher
{
    /**
     * Entity Manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Listeners Invoker
     *
     * @var \Doctrine\ORM\Event\ListenersInvoker
     */
    private $invoker;

    /**
     * Metadata Factory
     *
     * @var \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * The query hints
     *
     * @var array
     */
    private $hints = array();

    /**
     * Entities enqueued for postLoad dispatching
     *
     * @var array
     */
    private $entities = array();

    /**
     * Constructs a new dispatcher instance
     *
     * @param EntityManager $em
     * @param array $hints
     */
    public function __construct(EntityManager $em, array $hints = array())
    {
        $this->entityManager = $em;
        $this->metadataFactory = $em->getMetadataFactory();
        $this->invoker = $this->entityManager->getUnitOfWork()->getListenersInvoker();
        $this->hints = $hints;
    }

    /**
     * Dispatches postLoad event for specified entity or enqueues it for later dispatching
     *
     * @param object $entity
     */
    public function dispatchPostLoad($entity)
    {
        $className = get_class($entity);
        $meta = $this->metadataFactory->getMetadataFor($className);
        $invoke = $this->invoker->getSubscribedSystems($meta, Events::postLoad);

        if ($invoke === ListenersInvoker::INVOKE_NONE) {
            return;
        }

        if (isset($this->hints[Query::HINT_INTERNAL_ITERATION])) {
            $this->invoker->invoke($meta, Events::postLoad, $entity, new LifecycleEventArgs($entity, $this->entityManager), $invoke);
        } else {
            if ( ! isset($this->entities[$className])) {
                $this->entities[$className] = array();
            }

            $this->entities[$className][] = $entity;
        }
    }

    /**
     * Dispatches all enqueued postLoad events
     */
    public function dispatchEnqueuedPostLoadEvents()
    {
        foreach ($this->entities as $class => $entities) {
            $meta = $this->metadataFactory->getMetadataFor($class);
            $invoke = $this->invoker->getSubscribedSystems($meta, Events::postLoad);

            foreach ($entities as $entity) {
                $this->invoker->invoke($meta, Events::postLoad, $entity, new LifecycleEventArgs($entity, $this->entityManager), $invoke);
            }
        }
    }
}
