<?php 
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 * 
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link www.doctrine-project.org
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class EntityRepository
{
    private $_entityName;
    private $_em;
    private $_class;
    
    /**
     * Initializes a new <tt>EntityRepository</tt>.
     * 
     * @param EntityManager $em The EntityManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        $this->_entityName = $class->name;
        $this->_em = $em;
        $this->_class = $class;
    }
    
    /**
     * Clears the repository, causing all managed entities to become detached.
     */
    public function clear()
    {
        $this->_em->clear($this->_class->rootEntityName);
    }
    
    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param $id The identifier.
     * @param int $hydrationMode The hydration mode to use.
     * @return object The entity.
     */
    public function find($id)
    {
        // Check identity map first
        if ($entity = $this->_em->getUnitOfWork()->tryGetById($id, $this->_class->rootEntityName)) {
            return $entity; // Hit!
        }

        if ( ! is_array($id) || count($id) <= 1) {
            $value = is_array($id) ? array_values($id) : array($id);
            $id = array_combine($this->_class->identifier, $value);
        }

        return $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName)->load($id);
    }

    /**
     * Finds all entities in the repository.
     *
     * @param int $hydrationMode
     * @return array The entities.
     */
    public function findAll()
    {
        return $this->findBy(array());
    }
    
    /**
     * Finds entities by a set of criteria.
     *
     * @param string $column 
     * @param string $value 
     * @return array
     */
    public function findBy(array $criteria)
    {
        return $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName)->loadAll($criteria);
    }
    
    /**
     * Finds a single entity by a set of criteria.
     *
     * @param string $column 
     * @param string $value
     * @return object
     */
    public function findOneBy(array $criteria)
    {
        return $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName)->load($criteria);
    }
    
    /**
     * Adds support for magic finders.
     *
     * @return array|object The found entity/entities.
     * @throws BadMethodCallException  If the method called is an invalid find* method
     *                                    or no find* method at all and therefore an invalid
     *                                    method call.
     */
    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by = substr($method, 6, strlen($method));
            $method = 'findBy';
        } else if (substr($method, 0, 9) == 'findOneBy') {
            $by = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        } else {
            throw new BadMethodCallException("Undefined method '$method'.");
        }
        
        if ( ! isset($arguments[0])) {
            throw DoctrineException::updateMe('You must specify the value to findBy.');
        }

        $fieldName = lcfirst(\Doctrine\Common\Util\Inflector::classify($by));

        if ($this->_class->hasField($fieldName)) {
            return $this->$method(array($fieldName => $arguments[0]));
        } else {
            throw \Doctrine\Common\DoctrineException::updateMe('Cannot find by: ' . $by . '. Invalid field.');
        }
    }
}