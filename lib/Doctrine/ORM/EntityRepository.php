<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BadMethodCallException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Repository\Exception\InvalidMagicMethodCall;
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
class EntityRepository implements ObjectRepository, Selectable
{
    /** @var string */
    protected $entityName;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var ClassMetadata */
    protected $class;

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     */
    public function __construct(EntityManagerInterface $em, Mapping\ClassMetadata $class)
    {
        $this->entityName = $class->getClassName();
        $this->em         = $em;
        $this->class      = $class;
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
        return $this->em->createQueryBuilder()
            ->select($alias)
            ->from($this->entityName, $alias, $indexBy);
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
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->entityName, $alias);

        return $rsm;
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     */
    public function clear()
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
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->em->find($this->entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @return object[] The entities.
     */
    public function findAll()
    {
        return $this->findBy([]);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param mixed[]  $criteria
     * @param mixed[]  $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return object[] The objects.
     *
     * @todo guilhermeblanco Change orderBy to use a blank array by default (requires Common\Persistence change).
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy ?? [], $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param mixed[] $criteria
     * @param mixed[] $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = [])
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @param Criteria[] $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     */
    public function count(array $criteria)
    {
        return $this->em->getUnitOfWork()->getEntityPersister($this->entityName)->count($criteria);
    }

    /**
     * Adds support for magic method calls.
     *
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return mixed The returned value from the resolved method.
     *
     * @throws ORMException
     * @throws BadMethodCallException If the method called is invalid.
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

        throw new BadMethodCallException(
            sprintf(
                "Undefined method '%s'. The method name must start with either findBy, findOneBy or countBy!",
                $method
            )
        );
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
     * @return EntityManagerInterface
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
     * @return Collection|object[]
     */
    public function matching(Criteria $criteria)
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
     * @return mixed
     *
     * @throws ORMException If the method called is invalid or the requested field/association does not exist.
     */
    private function resolveMagicCall($method, $by, array $arguments)
    {
        if (! $arguments) {
            throw InvalidMagicMethodCall::onMissingParameter($method . $by);
        }

        $fieldName = lcfirst(Inflector::classify($by));

        if ($this->class->getProperty($fieldName) === null) {
            throw InvalidMagicMethodCall::becauseFieldNotFoundIn(
                $this->entityName,
                $fieldName,
                $method . $by
            );
        }

        return $this->{$method}([$fieldName => $arguments[0]], ...array_slice($arguments, 1));
    }
}
