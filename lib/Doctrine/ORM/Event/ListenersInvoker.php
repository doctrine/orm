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

use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\EventArgs;

/**
 * A method invoker based on entity lifecycle.
 *
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 * @since   2.4
 */
class ListenersInvoker
{
    /**
     * @var \Doctrine\ORM\Mapping\EntityListenerResolver The Entity listener resolver.
     */
    private $resolver;

    /**
     * @param \Doctrine\ORM\Mapping\EntityListenerResolver $resolver
     */
    public function __construct(EntityListenerResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Dispatches the lifecycle event of the given entity to the registered lifecycle callbacks.
     *
     * @param string $eventName                 The entity lifecycle event.
     * @param \Object $entity                   The Entity on which the event occured.
     * @param \Doctrine\Common\EventArgs $event The Event args.
     */
    public function invokeLifecycleCallbacks(ClassMetadata $metadata, $eventName, $entity, EventArgs $event)
    {
        foreach ($metadata->lifecycleCallbacks[$eventName] as $callback) {
            $entity->$callback($event);
        }
    }

    /**
     * Dispatches the lifecycle event of the given entity to the registered entity listeners.
     *
     * @param string $eventName                     The entity lifecycle event.
     * @param object $entity                        The Entity on which the event occured.
     * @param \Doctrine\Common\EventArgs $event     The Event args.
     */
    public function invokeEntityListeners(ClassMetadata $metadata, $eventName, $entity, EventArgs $event)
    {
        foreach ($metadata->entityListeners[$eventName] as $listener) {
            $class      = $listener['class'];
            $method     = $listener['method'];
            $instance   = $this->resolver->resolve($class);

            $instance->$method($entity, $event);
        }
    }
}