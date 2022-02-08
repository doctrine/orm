<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use BadMethodCallException;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\ORM\Exception\InvalidHydrationMode;
use Doctrine\ORM\Exception\ManagerException;
use Doctrine\ORM\Exception\MismatchedEventManager;
use Doctrine\ORM\Exception\MissingIdentifierField;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Exception\UnrecognizedIdentifierFields;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Throwable;

use function array_keys;
use function is_array;
use function is_object;
use function ltrim;

/**
 * The EntityManager is the central access point to ORM functionality.
 *
 * It is a facade to all different ORM subsystems such as UnitOfWork,
 * Query Language and Repository API. Instantiation is done through
 * the static create() method. The quickest way to obtain a fully
 * configured EntityManager is:
 *
 *     use Doctrine\ORM\Tools\Setup;
 *     use Doctrine\ORM\EntityManager;
 *
 *     $paths = array('/path/to/entity/mapping/files');
 *
 *     $config = Setup::createAnnotationMetadataConfiguration($paths);
 *     $dbParams = array('driver' => 'pdo_sqlite', 'memory' => true);
 *     $entityManager = EntityManager::create($dbParams, $config);
 *
 * For more information see
 * {@link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html}
 *
 * You should never attempt to inherit from the EntityManager: Inheritance
 * is not a valid extension point for the EntityManager. Instead you
 * should take a look at the {@see \Doctrine\ORM\Decorator\EntityManagerDecorator}
 * and wrap your entity manager in a decorator.
 */
/* final */class EntityManager implements EntityManagerInterface
{
    /**
     * The used Configuration.
     */
    private Configuration $config;

    /**
     * The database connection used by the EntityManager.
     */
    private Connection $conn;

    /**
     * The metadata factory, used to retrieve the ORM metadata of entity classes.
     */
    private ClassMetadataFactory $metadataFactory;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     */
    private UnitOfWork $unitOfWork;

    /**
     * The event manager that is the central point of the event system.
     */
    private EventManager $eventManager;

    /**
     * The proxy factory used to create dynamic proxies.
     */
    private ProxyFactory $proxyFactory;

    /**
     * The repository factory used to create dynamic repositories.
     */
    private RepositoryFactory $repositoryFactory;

    /**
     * The expression builder instance used to generate query expressions.
     */
    private ?Expr $expressionBuilder = null;

    /**
     * Whether the EntityManager is closed or not.
     */
    private bool $closed = false;

    /**
     * Collection of query filters.
     */
    private ?FilterCollection $filterCollection = null;

    /**
     * The second level cache regions API.
     */
    private ?Cache $cache = null;

    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     */
    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $this->conn         = $conn;
        $this->config       = $config;
        $this->eventManager = $eventManager;

        $metadataFactoryClassName = $config->getClassMetadataFactoryName();

        $this->metadataFactory = new $metadataFactoryClassName();
        $this->metadataFactory->setEntityManager($this);

        $this->configureMetadataCache();

        $this->repositoryFactory = $config->getRepositoryFactory();
        $this->unitOfWork        = new UnitOfWork($this);
        $this->proxyFactory      = new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
            $config->getAutoGenerateProxyClasses()
        );

        if ($config->isSecondLevelCacheEnabled()) {
            $cacheConfig  = $config->getSecondLevelCacheConfiguration();
            $cacheFactory = $cacheConfig->getCacheFactory();
            $this->cache  = $cacheFactory->createCache($this);
        }
    }

    public function getConnection(): Connection
    {
        return $this->conn;
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->expressionBuilder ??= new Expr();
    }

    public function beginTransaction(): void
    {
        $this->conn->beginTransaction();
    }

    public function getCache(): ?Cache
    {
        return $this->cache;
    }

    public function wrapInTransaction(callable $func): mixed
    {
        $this->conn->beginTransaction();

        try {
            $return = $func($this);

            $this->flush();
            $this->conn->commit();

            return $return;
        } catch (Throwable $e) {
            $this->close();
            $this->conn->rollBack();

            throw $e;
        }
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollBack();
    }

    /**
     * Returns the ORM metadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)) or an aliased class name.
     *
     * Examples:
     * MyProject\Domain\User
     * sales:PriceRequest
     *
     * Internal note: Performance-sensitive method.
     *
     * {@inheritDoc}
     */
    public function getClassMetadata($className): Mapping\ClassMetadata
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    public function createQuery(string $dql = ''): Query
    {
        $query = new Query($this);

        if (! empty($dql)) {
            $query->setDQL($dql);
        }

        return $query;
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        $query = new NativeQuery($this);

        $query->setSQL($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * If an entity is explicitly passed to this method only this entity and
     * the cascade-persist semantics + scheduled inserts/removals are synchronized.
     *
     * @throws OptimisticLockException If a version check on an entity that
     * makes use of optimistic locking fails.
     * @throws ORMException
     */
    public function flush(): void
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function find($className, mixed $id, ?int $lockMode = null, ?int $lockVersion = null): ?object
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($className, '\\'));

        if ($lockMode !== null) {
            $this->checkLockRequirements($lockMode, $class);
        }

        if (! is_array($id)) {
            if ($class->isIdentifierComposite) {
                throw ORMInvalidArgumentException::invalidCompositeIdentifier();
            }

            $id = [$class->identifier[0] => $id];
        }

        foreach ($id as $i => $value) {
            if (is_object($value) && $this->metadataFactory->hasMetadataFor(ClassUtils::getClass($value))) {
                $id[$i] = $this->unitOfWork->getSingleIdentifierValue($value);

                if ($id[$i] === null) {
                    throw ORMInvalidArgumentException::invalidIdentifierBindingEntity();
                }
            }
        }

        $sortedId = [];

        foreach ($class->identifier as $identifier) {
            if (! isset($id[$identifier])) {
                throw MissingIdentifierField::fromFieldAndClass($identifier, $class->name);
            }

            $sortedId[$identifier] = $id[$identifier];
            unset($id[$identifier]);
        }

        if ($id) {
            throw UnrecognizedIdentifierFields::fromClassAndFieldNames($class->name, array_keys($id));
        }

        $unitOfWork = $this->getUnitOfWork();

        $entity = $unitOfWork->tryGetById($sortedId, $class->rootEntityName);

        // Check identity map first
        if ($entity !== false) {
            if (! ($entity instanceof $class->name)) {
                return null;
            }

            switch (true) {
                case $lockMode === LockMode::OPTIMISTIC:
                    $this->lock($entity, $lockMode, $lockVersion);
                    break;

                case $lockMode === LockMode::NONE:
                case $lockMode === LockMode::PESSIMISTIC_READ:
                case $lockMode === LockMode::PESSIMISTIC_WRITE:
                    $persister = $unitOfWork->getEntityPersister($class->name);
                    $persister->refresh($sortedId, $entity, $lockMode);
                    break;
            }

            return $entity; // Hit!
        }

        $persister = $unitOfWork->getEntityPersister($class->name);

        switch (true) {
            case $lockMode === LockMode::OPTIMISTIC:
                $entity = $persister->load($sortedId);

                if ($entity !== null) {
                    $unitOfWork->lock($entity, $lockMode, $lockVersion);
                }

                return $entity;

            case $lockMode === LockMode::PESSIMISTIC_READ:
            case $lockMode === LockMode::PESSIMISTIC_WRITE:
                return $persister->load($sortedId, null, null, [], $lockMode);

            default:
                return $persister->loadById($sortedId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getReference(string $entityName, $id): ?object
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($entityName, '\\'));

        if (! is_array($id)) {
            $id = [$class->identifier[0] => $id];
        }

        $sortedId = [];

        foreach ($class->identifier as $identifier) {
            if (! isset($id[$identifier])) {
                throw MissingIdentifierField::fromFieldAndClass($identifier, $class->name);
            }

            $sortedId[$identifier] = $id[$identifier];
            unset($id[$identifier]);
        }

        if ($id) {
            throw UnrecognizedIdentifierFields::fromClassAndFieldNames($class->name, array_keys($id));
        }

        $entity = $this->unitOfWork->tryGetById($sortedId, $class->rootEntityName);

        // Check identity map first, if its already in there just return it.
        if ($entity !== false) {
            return $entity instanceof $class->name ? $entity : null;
        }

        if ($class->subClasses) {
            return $this->find($entityName, $sortedId);
        }

        $entity = $this->proxyFactory->getProxy($class->name, $sortedId);

        $this->unitOfWork->registerManaged($entity, $sortedId, []);

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function getPartialReference(string $entityName, $identifier): ?object
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($entityName, '\\'));

        $entity = $this->unitOfWork->tryGetById($identifier, $class->rootEntityName);

        // Check identity map first, if its already in there just return it.
        if ($entity !== false) {
            return $entity instanceof $class->name ? $entity : null;
        }

        if (! is_array($identifier)) {
            $identifier = [$class->identifier[0] => $identifier];
        }

        $entity = $class->newInstance();

        $class->setIdentifierValues($entity, $identifier);

        $this->unitOfWork->registerManaged($entity, $identifier, []);
        $this->unitOfWork->markReadOnly($entity);

        return $entity;
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string|null $objectName The object name (not supported).
     *
     * @throws ORMInvalidArgumentException If the caller attempted to clear the EM partially by passing an object name.
     */
    public function clear($objectName = null): void
    {
        if ($objectName !== null) {
            throw new ORMInvalidArgumentException('Clearing the entity manager partially if not supported.');
        }

        $this->unitOfWork->clear();
    }

    public function close(): void
    {
        $this->clear();

        $this->closed = true;
    }

    /**
     * Tells the EntityManager to make an instance managed and persistent.
     *
     * The entity will be entered into the database at or before transaction
     * commit or as a result of the flush operation.
     *
     * NOTE: The persist operation always considers entities that are not yet known to
     * this EntityManager as NEW. Do not pass detached entities to the persist operation.
     *
     * @param object $entity The instance to make managed and persistent.
     *
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    public function persist($entity): void
    {
        if (! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#persist()', $entity);
        }

        $this->errorIfClosed();

        $this->unitOfWork->persist($entity);
    }

    /**
     * Removes an entity instance.
     *
     * A removed entity will be removed from the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $entity The entity instance to remove.
     *
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    public function remove($entity): void
    {
        if (! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#remove()', $entity);
        }

        $this->errorIfClosed();

        $this->unitOfWork->remove($entity);
    }

    /**
     * Refreshes the persistent state of an entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity The entity to refresh.
     *
     * @throws ORMInvalidArgumentException
     * @throws ORMException
     */
    public function refresh($entity): void
    {
        if (! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#refresh()', $entity);
        }

        $this->errorIfClosed();

        $this->unitOfWork->refresh($entity);
    }

    /**
     * Detaches an entity from the EntityManager, causing a managed entity to
     * become detached.  Unflushed changes made to the entity if any
     * (including removal of the entity), will not be synchronized to the database.
     * Entities which previously referenced the detached entity will continue to
     * reference it.
     *
     * @param object $entity The entity to detach.
     *
     * @throws ORMInvalidArgumentException
     */
    public function detach($entity): void
    {
        if (! is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#detach()', $entity);
        }

        $this->unitOfWork->detach($entity);
    }

    /**
     * Not supported.
     *
     * @param object $object
     *
     * @psalm-return never
     */
    public function merge($object): object
    {
        throw new BadMethodCallException('The merge operation is not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function lock(object $entity, int $lockMode, $lockVersion = null): void
    {
        $this->unitOfWork->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     * @psalm-param class-string<T> $entityName
     *
     * @return ObjectRepository|EntityRepository The repository class.
     * @psalm-return EntityRepository<T>
     *
     * @template T
     */
    public function getRepository($entityName): EntityRepository
    {
        return $this->repositoryFactory->getRepository($this, $entityName);
    }

    /**
     * Determines whether an entity instance is managed in this EntityManager.
     *
     * @param object $entity
     *
     * @return bool TRUE if this EntityManager currently manages the given entity, FALSE otherwise.
     */
    public function contains($entity): bool
    {
        return $this->unitOfWork->isScheduledForInsert($entity)
            || $this->unitOfWork->isInIdentityMap($entity)
            && ! $this->unitOfWork->isScheduledForDelete($entity);
    }

    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    /**
     * Throws an exception if the EntityManager is closed or currently not active.
     *
     * @throws EntityManagerClosed If the EntityManager is closed.
     */
    private function errorIfClosed(): void
    {
        if ($this->closed) {
            throw EntityManagerClosed::create();
        }
    }

    public function isOpen(): bool
    {
        return ! $this->closed;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode): AbstractHydrator
    {
        switch ($hydrationMode) {
            case Query::HYDRATE_OBJECT:
                return new Internal\Hydration\ObjectHydrator($this);

            case Query::HYDRATE_ARRAY:
                return new Internal\Hydration\ArrayHydrator($this);

            case Query::HYDRATE_SCALAR:
                return new Internal\Hydration\ScalarHydrator($this);

            case Query::HYDRATE_SINGLE_SCALAR:
                return new Internal\Hydration\SingleScalarHydrator($this);

            case Query::HYDRATE_SIMPLEOBJECT:
                return new Internal\Hydration\SimpleObjectHydrator($this);

            case Query::HYDRATE_SCALAR_COLUMN:
                return new Internal\Hydration\ScalarColumnHydrator($this);

            default:
                $class = $this->config->getCustomHydrationMode($hydrationMode);

                if ($class !== null) {
                    return new $class($this);
                }
        }

        throw InvalidHydrationMode::fromMode((string) $hydrationMode);
    }

    public function getProxyFactory(): ProxyFactory
    {
        return $this->proxyFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj): void
    {
        $this->unitOfWork->initializeObject($obj);
    }

    /**
     * Factory method to create EntityManager instances.
     *
     * @param mixed[]|Connection $connection An array with the connection parameters or an existing Connection instance.
     * @psalm-param array<string, mixed>|Connection $connection
     *
     * @throws DBALException
     * @throws ManagerException
     */
    public static function create(array|Connection $connection, Configuration $config, ?EventManager $eventManager = null): EntityManager
    {
        if (! $config->getMetadataDriverImpl()) {
            throw MissingMappingDriverImplementation::create();
        }

        $connection = static::createConnection($connection, $config, $eventManager);

        return new EntityManager($connection, $config, $connection->getEventManager());
    }

    /**
     * Factory method to create Connection instances.
     *
     * @param mixed[]|Connection $connection An array with the connection parameters or an existing Connection instance.
     * @psalm-param array<string, mixed>|Connection $connection
     *
     * @throws DBALException
     * @throws ManagerException
     */
    protected static function createConnection(array|Connection $connection, Configuration $config, ?EventManager $eventManager = null): Connection
    {
        if (is_array($connection)) {
            return DriverManager::getConnection($connection, $config, $eventManager ?? new EventManager());
        }

        if ($eventManager !== null && $connection->getEventManager() !== $eventManager) {
            throw MismatchedEventManager::create();
        }

        return $connection;
    }

    public function getFilters(): FilterCollection
    {
        return $this->filterCollection ??= new FilterCollection($this);
    }

    public function isFiltersStateClean(): bool
    {
        return $this->filterCollection === null || $this->filterCollection->isClean();
    }

    public function hasFilters(): bool
    {
        return $this->filterCollection !== null;
    }

    /**
     * @psalm-param LockMode::* $lockMode
     *
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function checkLockRequirements(int $lockMode, ClassMetadata $class): void
    {
        switch ($lockMode) {
            case LockMode::OPTIMISTIC:
                if (! $class->isVersioned) {
                    throw OptimisticLockException::notVersioned($class->name);
                }

                break;
            case LockMode::PESSIMISTIC_READ:
            case LockMode::PESSIMISTIC_WRITE:
                if (! $this->getConnection()->isTransactionActive()) {
                    throw TransactionRequiredException::transactionRequired();
                }
        }
    }

    private function configureMetadataCache(): void
    {
        $metadataCache = $this->config->getMetadataCache();
        if (! $metadataCache) {
            return;
        }

        $this->metadataFactory->setCache($metadataCache);
    }
}
