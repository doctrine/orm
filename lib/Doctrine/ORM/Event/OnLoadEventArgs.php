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
use Doctrine\Common\EventArgs;

/**
 * Lifecycle Events are triggered by the UnitOfWork during lifecycle transitions
 * of entities.
 *
 * @link   www.doctrine-project.org
 * @since  2.2
 * @author Tom Anderson <tanderson@soliantconsulting.com>
 */
class OnLoadEventArgs extends EventArgs
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var object
     */
    private $entity;

    /**
     * Constructor
     *
     * @param object $object
     * @param ObjectManager $objectManager
     */
    public function __construct($entity, EntityManager $entityManager)
    {
        $this->setEntity($entity);
        $this->setEntityManager($entityManager);
    }

    /**
     * Retrieve associated entity.
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Retrieve associated ObjectManager.
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->entityManager;
    }

    private function setEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Sets associated Entity.
     *
     * @return this
     */
    public function setEntity($value)
    {
        $this->entity = $value;
        return $this;
    }
}
