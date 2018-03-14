<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use function array_slice;
use function lcfirst;
use function sprintf;
use function strpos;
use function substr;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 */
class EntityRepository
{
    /** @var string */
    protected $entityName;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var ClassMetadata */
    protected $class;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @param EntityManagerInterface $em    The EntityManager to use.
     * @param Mapping\ClassMetadata  $class The class descriptor.
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        $this->entityName = $class->getClassName();
        $this->em         = $em;
        $this->class      = $class;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $indexBy The index for the from.
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null) : QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias, $indexBy);
    }

    /**
     * Creates a new result set mapping builder for this entity.
     *
     * The column naming strategy is "INCREMENT".
     */
    public function createResultSetMappingBuilder(string $alias) : ResultSetMappingBuilder
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->entityName, $alias);

        return $rsm;
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     */
    public function clear() : void
    {
        $this->em->clear($this->class->getRootClassName());
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
     */
    public function find($id, ?int $lockMode = null, ?int $lockVersion = null) : ?object
    {
        return $this->em->find($this->entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @return object[] The entities.
     */
    public function findAll() : array
    {
        return $this->findBy([]);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     *
     * @return object[] The objects.
     *
     * @todo guilhermeblanco Change orderBy to use a blank array by default (requires Common\Persistence change).
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null) : array
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy !== null ? $orderBy : [], $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = []) : ?object
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     *
     * @param Criteria[] $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     */
    public function count(array $criteria) : int
    {
        return $this->em->getUnitOfWork()->getEntityPersister($this->entityName)->count($criteria);
    }

    /**
     * Adds support for magic method calls.
     *
     * @param mixed[] $arguments
     *
     * @return mixed The returned value from the resolved method.
     *
     * @throws ORMException
     * @throws \BadMethodCallException If the method called is invalid.
     */
    public function __call(string $method, array $arguments)
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

        throw new \BadMethodCallException(
            sprintf(
                "Undefined method '%s'. The method name must start with either findBy, findOneBy or countBy!",
                $method
            )
        );
    }

    protected function getEntityName() : string
    {
        return $this->entityName;
    }

    public function getClassName() : string
    {
        return $this->getEntityName();
    }

    protected function getEntityManager() : EntityManagerInterface
    {
        return $this->em;
    }

    protected function getClassMetadata() : ClassMetadata
    {
        return $this->class;
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @return Collection|object[]
     */
    public function matching(Criteria $criteria) : Collection
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }

    /**
     * Resolves a magic method call to the proper existent method at `EntityRepository`.
     *
     * @param string  $method    The method to call
     * @param string  $by        The property name used as condition
     * @param mixed[] $arguments The arguments to pass at method call
     *
     * @throws ORMException If the method called is invalid or the requested field/association does not exist.
     *
     * @return mixed
     */
    private function resolveMagicCall(string $method, string $by, array $arguments)
    {
        if (! $arguments) {
            throw ORMException::findByRequiresParameter($method . $by);
        }

        $fieldName = lcfirst(Inflector::classify($by));

        if ($this->class->getProperty($fieldName) === null) {
            throw ORMException::invalidMagicCall($this->entityName, $fieldName, $method . $by);
        }

        return $this->{$method}([$fieldName => $arguments[0]], ...array_slice($arguments, 1));
    }
}
