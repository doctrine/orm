<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BadMethodCallException;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\LockMode;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Repository\Exception\InvalidMagicMethodCall;
use Doctrine\Persistence\ObjectRepository;

use function array_slice;
use function lcfirst;
use function sprintf;
use function str_starts_with;
use function substr;

/**
 * An EntityRepository serves as a repository for entities with generic as well as
 * business specific methods for retrieving entities.
 *
 * This class is designed for inheritance and users can subclass this class to
 * write their own repositories with business-specific methods to locate entities.
 *
 * @template T of object
 * @template-implements Selectable<int,T>
 * @template-implements ObjectRepository<T>
 */
class EntityRepository implements ObjectRepository, Selectable
{
    /** @psalm-var class-string<T> */
    private readonly string $entityName;
    private static ?Inflector $inflector = null;

    /**
     * @psalm-param ClassMetadata<T> $class
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClassMetadata $class
    ) {
        $this->entityName = $class->name;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
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
    public function createResultSetMappingBuilder(string $alias): ResultSetMappingBuilder
    {
        $rsm = new ResultSetMappingBuilder($this->em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->entityName, $alias);

        return $rsm;
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param LockMode|int|null $lockMode One of the \Doctrine\DBAL\LockMode::* constants
     *                                    or NULL if no specific lock mode should be used
     *                                    during the search.
     * @psalm-param LockMode::*|null $lockMode
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     * @psalm-return ?T
     */
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return $this->em->find($this->entityName, $id, $lockMode, $lockVersion);
    }

    /**
     * Finds all entities in the repository.
     *
     * @psalm-return list<T> The entities.
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * {@inheritdoc}
     *
     * @psalm-return list<T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @psalm-param array<string, mixed> $criteria
     * @psalm-param array<string, string>|null $orderBy
     *
     * @psalm-return T|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @psalm-param array<string, mixed> $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     */
    public function count(array $criteria = []): int
    {
        return $this->em->getUnitOfWork()->getEntityPersister($this->entityName)->count($criteria);
    }

    /**
     * Adds support for magic method calls.
     *
     * @param mixed[] $arguments
     * @psalm-param list<mixed> $arguments
     *
     * @throws BadMethodCallException If the method called is invalid.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (str_starts_with($method, 'findBy')) {
            return $this->resolveMagicCall('findBy', substr($method, 6), $arguments);
        }

        if (str_starts_with($method, 'findOneBy')) {
            return $this->resolveMagicCall('findOneBy', substr($method, 9), $arguments);
        }

        if (str_starts_with($method, 'countBy')) {
            return $this->resolveMagicCall('count', substr($method, 7), $arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Undefined method "%s". The method name must start with ' .
            'either findBy, findOneBy or countBy!',
            $method
        ));
    }

    /**
     * @psalm-return class-string<T>
     */
    protected function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getClassName(): string
    {
        return $this->getEntityName();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @psalm-return ClassMetadata<T>
     */
    protected function getClassMetadata(): ClassMetadata
    {
        return $this->class;
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @psalm-return AbstractLazyCollection<int, T>&Selectable<int, T>
     */
    public function matching(Criteria $criteria): AbstractLazyCollection
    {
        $persister = $this->em->getUnitOfWork()->getEntityPersister($this->entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }

    /**
     * Resolves a magic method call to the proper existent method at `EntityRepository`.
     *
     * @param string $method The method to call
     * @param string $by     The property name used as condition
     * @psalm-param list<mixed> $arguments The arguments to pass at method call
     *
     * @throws InvalidMagicMethodCall If the method called is invalid or the
     *                                requested field/association does not exist.
     */
    private function resolveMagicCall(string $method, string $by, array $arguments): mixed
    {
        if (! $arguments) {
            throw InvalidMagicMethodCall::onMissingParameter($method . $by);
        }

        self::$inflector ??= InflectorFactory::create()->build();

        $fieldName = lcfirst(self::$inflector->classify($by));

        if (! ($this->class->hasField($fieldName) || $this->class->hasAssociation($fieldName))) {
            throw InvalidMagicMethodCall::becauseFieldNotFoundIn(
                $this->entityName,
                $fieldName,
                $method . $by
            );
        }

        return $this->$method([$fieldName => $arguments[0]], ...array_slice($arguments, 1));
    }
}
