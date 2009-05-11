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
 * A repository provides the illusion of an in-memory Entity store. 
 * Base class for all custom user-defined repositories.
 * Provides basic finder methods, common to all repositories.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Roman Borschel <roman@code-factory.org>
 */
class EntityRepository
{
    protected $_entityName;
    protected $_em;
    protected $_classMetadata;
    
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $classMetadata)
    {
        $this->_entityName = $classMetadata->getClassName();
        $this->_em = $em;
        $this->_classMetadata = $classMetadata;
    }
    
    /**
     * creates a new Doctrine_Query object and adds the component name
     * of this table as the query 'from' part
     *
     * @param string Optional alias name for component aliasing.
     *
     * @return Doctrine_Query
     */
    protected function _createQuery($alias = '')
    {
        if ( ! empty($alias)) {
            $alias = ' ' . trim($alias);
        }
        return $this->_em->createQuery()->from($this->_entityName . $alias);
    }
    
    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear()
    {
        $this->_em->getUnitOfWork()->clearIdentitiesForEntity($this->_classMetadata->getRootClassName());
    }
    
    /**
     * Finds an entity by its primary key.
     *
     * @param $id                       The identifier.
     * @param int $hydrationMode        The hydration mode to use.
     * @return mixed                    Array or Doctrine_Entity or false if no result
     */
    public function find($id, $hydrationMode = null)
    {
        if (is_array($id) && count($id) > 1) {
            // it's a composite key. keys = field names, values = values.
            $values = array_values($id);
            $keys = array_keys($id);
        } else {
            $values = is_array($id) ? array_values($id) : array($id);
            $keys = $this->_classMetadata->getIdentifier();
        }
        
        // Check identity map first
        if ($entity = $this->_em->getUnitOfWork()->tryGetById($id, $this->_classMetadata->getRootClassName())) {
            return $entity; // Hit!
        }

        $dql = 'select e from ' . $this->_classMetadata->getClassName() . ' e where ';
        $conditionDql = '';
        $paramIndex = 1;
        foreach ($keys as $key) {
            if ($conditionDql != '') $conditionDql .= ' and ';
            $conditionDql .= 'e.' . $key . ' = ?' . $paramIndex++;
        }
        $dql .= $conditionDql;

        $q = $this->_em->createQuery($dql);
        foreach ($values as $index => $value) {
            $q->setParameter($index, $value);
        }

        return $q->getSingleResult($hydrationMode);
    }

    /**
     * Finds all entities in the repository.
     *
     * @param int $hydrationMode
     * @return mixed
     */
    public function findAll($hydrationMode = null)
    {
        return $this->_createQuery()->execute(array(), $hydrationMode);
    }
    
    /**
     * findBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findBy($fieldName, $value, $hydrationMode = null)
    {
        return $this->_createQuery()->where($fieldName . ' = ?')->execute(array($value), $hydrationMode);
    }
    
    /**
     * findOneBy
     *
     * @param string $column 
     * @param string $value 
     * @param string $hydrationMode 
     * @return void
     */
    protected function findOneBy($fieldName, $value, $hydrationMode = null)
    {
        $results = $this->_createQuery()->where($fieldName . ' = ?')->limit(1)->execute(
                array($value), $hydrationMode);
        return $hydrationMode === Doctrine::HYDRATE_ARRAY ? array_shift($results) : $results->getFirst();
    }
    
    /**
     * findBySql
     * finds records with given SQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @param int $hydrationMode        Query::HYDRATE_ARRAY or Query::HYDRATE_RECORD
     * @return Doctrine_Collection
     * 
     * @todo This actually takes DQL, not SQL, but it requires column names 
     *       instead of field names. This should be fixed to use raw SQL instead.
     */
    public function findBySql($dql, array $params = array(), $hydrationMode = null)
    {
        return $this->_createQuery()->where($dql)->execute($params, $hydrationMode);
    }

    /**
     * findByDql
     * finds records with given DQL where clause
     * returns a collection of records
     *
     * @param string $dql               DQL after WHERE clause
     * @param array $params             query parameters
     * @param int $hydrationMode        Query::HYDRATE_ARRAY or Query::HYDRATE_RECORD
     * @return Doctrine_Collection
     */
    public function findByDql($dql, array $params = array(), $hydrationMode = null)
    {
        $query = new Query($this->_em);
        $component = $this->getComponentName();
        $dql = 'FROM ' . $component . ' WHERE ' . $dql;

        return $query->query($dql, $params, $hydrationMode);        
    }
    
    /**
     * Adds support for magic finders.
     * findByColumnName, findByRelationAlias
     * findById, findByContactId, etc.
     *
     * @return void
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
        
        if (isset($by)) {
            if ( ! isset($arguments[0])) {
                throw \Doctrine\Common\DoctrineException::updateMe('You must specify the value to findBy.');
            }
            
            $fieldName = Doctrine::tableize($by);
            $hydrationMode = isset($arguments[1]) ? $arguments[1]:null;
            
            if ($this->_classMetadata->hasField($fieldName)) {
                return $this->$method($fieldName, $arguments[0], $hydrationMode);
            } else if ($this->_classMetadata->hasRelation($by)) {
                $relation = $this->_classMetadata->getRelation($by);
                if ($relation['type'] === Doctrine_Relation::MANY) {
                    throw \Doctrine\Common\DoctrineException::updateMe('Cannot findBy many relationship.');
                }
                return $this->$method($relation['local'], $arguments[0], $hydrationMode);
            } else {
                throw \Doctrine\Common\DoctrineException::updateMe('Cannot find by: ' . $by . '. Invalid field or relationship alias.');
            }
        }
    }
}