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

namespace Doctrine\ORM;

use Doctrine\DBAL\LockMode;
use Doctrine\Common\Persistence\ObjectRepository;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityRepository implements ObjectRepository, Selectable
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $class;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @param EntityManager $em The EntityManager to use.
     * @param Mapping\ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct($em, Mapping\ClassMetadata $class)
    {
        $this->entityName = $class->name;
        $this->em         = $em;
        $this->class      = $class;
    }

    /**
     * Create a new QueryBuilder instance that is prepopulated for this entity name
     *
     * @param string $alias
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder($alias)
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias);
    }

    /**
     * Create a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     * @return Query
     */
    public function createNamedQuery($queryName)
    {
        return $this->em->createQuery($this->class->getNamedQuery($queryName));
    }

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     * @return NativeQuery
     */
    public function createNativeNamedQuery($queryName)
    {
        $queryMapping   = $this->class->getNamedNativeQuery($queryName);
        $rsm            = new Query\ResultSetMappingBuilder($this->em);
        $rsm->addNamedNativeQueryMapping($this->class, $queryMapping);

        return $this->em->createNativeQuery($queryMapping['query'], $rsm);
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     */
    public function clear()
    {
        $this->em->clear($this->class->rootEntityName);
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     * @param integer $lockMode
     * @param integer $lockVersion
     *
     * @return object The entity.
     */
    public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->em->find($this->entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @return array The entities.
     */
    public function findAll()
    {
        return $this->findBy(array());
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array $criteria
     * @return object
     */
    public function findOneBy(array $criteria)
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, array(), 0, 1);
    }

    /**
     * Adds support for magic finders.
     *
     * @return array|object The found entity/entities.
     * @throws BadMethodCallException  If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = substr($method, 6);
                $method = 'findBy';
                break;
            case (0 === strpos($method, 'findOneBy')):
                $by = substr($method, 9);
                $method = 'findOneBy';
                break;
            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        if (empty($arguments)) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        $fieldName = lcfirst(\Doctrine\Common\Util\Inflector::classify($by));

        if ($this->class->hasField($fieldName) || $this->class->hasAssociation($fieldName)) {
            switch (count($arguments)) {
                case 1:
                    return $this->$method(array($fieldName => $arguments[0]));
                case 2:
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1]);
                case 3:
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2]);
                case 4;
                    return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2], $arguments[3]);
                default:
                    // Do nothing
            }
        }

        throw ORMException::invalidFindByCall($this->entityName, $fieldName, $method.$by);
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->getEntityName();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    protected function getClassMetadata()
    {
        return $this->class;
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function matching(Criteria $criteria)
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return new ArrayCollection($persister->loadCriteria($criteria));
    }
}
