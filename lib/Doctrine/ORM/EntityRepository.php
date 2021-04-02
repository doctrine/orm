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

use BadMethodCallException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ObjectRepository;

use function array_slice;
use function lcfirst;
use function sprintf;
use function strpos;
use function substr;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 *
 * @template T
 * @template-implements Selectable<int,T>
 * @template-implements ObjectRepository<T>
 */
class EntityRepository implements ObjectRepository, Selectable
{
    /** @var string */
    protected $_entityName;

    /** @var EntityManager */
    protected $_em;

    /** @var ClassMetadata */
    protected $_class;

    /** @var Inflector */
    private static $inflector;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @psalm-param Mapping\ClassMetadata $class
     */
    public function __construct(EntityManagerInterface $em, Mapping\ClassMetadata $class)
    {
        $this->_entityName = $class->name;
        $this->_em         = $em;
        $this->_class      = $class;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        return $this->_em->createQueryBuilder()
            ->select($alias)
            ->from($this->_entityName, $alias, $indexBy);
    }

    /**
     * Creates a new result set mapping builder for this entity.
     *
     * The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias)
    {
        $rsm = new ResultSetMappingBuilder($this->_em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->_entityName, $alias);

        return $rsm;
    }

    /**
     * Creates a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     *
     * @return Query
     */
    public function createNamedQuery($queryName)
    {
        return $this->_em->createQuery($this->_class->getNamedQuery($queryName));
    }

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     *
     * @return NativeQuery
     */
    public function createNativeNamedQuery($queryName)
    {
        $queryMapping = $this->_class->getNamedNativeQuery($queryName);
        $rsm          = new Query\ResultSetMappingBuilder($this->_em);
        $rsm->addNamedNativeQueryMapping($this->_class, $queryMapping);

        return $this->_em->createNativeQuery($queryMapping['query'], $rsm);
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @deprecated 2.8 This method is being removed from the ORM and won't have any replacement
     *
     * @return void
     */
    public function clear()
    {
        @trigger_error('Method ' . __METHOD__ . '() is deprecated and will be removed in Doctrine ORM 3.0.', E_USER_DEPRECATED);

        $this->_em->clear($this->_class->rootEntityName);
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed    $id          The identifier.
     * @param int|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
     *                              or NULL if no specific lock mode should be used
     *                              during the search.
     * @param int|null $lockVersion The lock version.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     *
     * @psalm-return ?T
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->_em->find($this->_entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @psalm-return list<T> The entities.
     */
    public function findAll()
    {
        return $this->findBy([]);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param int|null $limit
     * @param int|null $offset
     *
     * @psalm-param array<string, mixed> $criteria
     * @psalm-param array<string, string>|null $orderBy
     * @psalm-return list<T> The objects.
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     *
     * @psalm-param array<string, mixed> $criteria
     * @psalm-param array<string, string>|null $orderBy
     * @psalm-return ?T
     */
    public function findOneBy(array $criteria, ?array $orderBy = null)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @return int The cardinality of the objects that match the given criteria.
     *
     * @psalm-param array<string, mixed> $criteria
     * @todo Add this method to `ObjectRepository` interface in the next major release
     */
    public function count(array $criteria)
    {
        return $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName)->count($criteria);
    }

    /**
     * Adds support for magic method calls.
     *
     * @param string $method
     *
     * @return mixed The returned value from the resolved method.
     *
     * @throws ORMException
     * @throws BadMethodCallException If the method called is invalid.
     *
     * @psalm-param list<mixed> $arguments
     */
    public function __call($method, $arguments)
    {
        if (strpos($method, 'findBy') === 0) {
            return $this->resolveMagicCall('findBy', substr($method, 6), $arguments);
        }

        if (strpos($method, 'findOneBy') === 0) {
            return $this->resolveMagicCall('findOneBy', substr($method, 9), $arguments);
        }

        if (strpos($method, 'countBy') === 0) {
            return $this->resolveMagicCall('count', substr($method, 7), $arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Undefined method "%s". The method name must start with ' .
            'either findBy, findOneBy or countBy!',
            $method
        ));
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return $this->_entityName;
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
        return $this->_em;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    protected function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @psalm-return Collection<int, T>
     */
    public function matching(Criteria $criteria)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }

    /**
     * Resolves a magic method call to the proper existent method at `EntityRepository`.
     *
     * @param string $method The method to call
     * @param string $by     The property name used as condition
     *
     * @return mixed
     *
     * @throws ORMException If the method called is invalid or the requested field/association does not exist.
     *
     * @psalm-param list<mixed> $arguments The arguments to pass at method call
     */
    private function resolveMagicCall(string $method, string $by, array $arguments)
    {
        if (! $arguments) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        if (self::$inflector === null) {
            self::$inflector = InflectorFactory::create()->build();
        }

        $fieldName = lcfirst(self::$inflector->classify($by));

        if (! ($this->_class->hasField($fieldName) || $this->_class->hasAssociation($fieldName))) {
            throw ORMException::invalidMagicCall($this->_entityName, $fieldName, $method . $by);
        }

        return $this->$method([$fieldName => $arguments[0]], ...array_slice($arguments, 1));
    }
}
