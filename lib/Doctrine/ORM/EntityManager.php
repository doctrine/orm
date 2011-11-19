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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Closure, Exception,
    Doctrine\Common\EventManager,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\LockMode,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataFactory,
    Doctrine\ORM\Query\ResultSetMapping,
    Doctrine\ORM\Proxy\ProxyFactory;

/**
 * The EntityManager is the central access point to ORM functionality.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityManager implements ObjectManager
{
    /**
     * The used Configuration.
     *
     * @var Doctrine\ORM\Configuration
     */
    private $config;

    /**
     * The database connection used by the EntityManager.
     *
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * The metadata factory, used to retrieve the ORM metadata of entity classes.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * The EntityRepository instances.
     *
     * @var array
     */
    private $repositories = array();

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var Doctrine\ORM\UnitOfWork
     */
    private $unitOfWork;

    /**
     * The event manager that is the central point of the event system.
     *
     * @var Doctrine\Common\EventManager
     */
    private $eventManager;

    /**
     * The maintained (cached) hydrators. One instance per type.
     *
     * @var array
     */
    private $hydrators = array();

    /**
     * The proxy factory used to create dynamic proxies.
     *
     * @var Doctrine\ORM\Proxy\ProxyFactory
     */
    private $proxyFactory;

    /**
     * The expression builder instance used to generate query expressions.
     *
     * @var Doctrine\ORM\Query\Expr
     */
    private $expressionBuilder;

    /**
     * Whether the EntityManager is closed or not.
     *
     * @var bool
     */
    private $closed = false;

    /**
     * Creates a new EntityManager that operates on the given database connection
     * and uses the given Configuration and EventManager implementations.
     *
     * @param Doctrine\DBAL\Connection $conn
     * @param Doctrine\ORM\Configuration $config
     * @param Doctrine\Common\EventManager $eventManager
     */
    protected function __construct(Connection $conn, Configuration $config, EventManager $eventManager)
    {
        $this->conn = $conn;
        $this->config = $config;
        $this->eventManager = $eventManager;

        $metadataFactoryClassName = $config->getClassMetadataFactoryName();
        $this->metadataFactory = new $metadataFactoryClassName;
        $this->metadataFactory->setEntityManager($this);
        $this->metadataFactory->setCacheDriver($this->config->getMetadataCacheImpl());

        $this->unitOfWork = new UnitOfWork($this);
        $this->proxyFactory = new ProxyFactory($this,
                $config->getProxyDir(),
                $config->getProxyNamespace(),
                $config->getAutoGenerateProxyClasses());
    }

    /**
     * Gets the database connection object used by the EntityManager.
     *
     * @return Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * Gets an ExpressionBuilder used for object-oriented construction of query expressions.
     *
     * Example:
     *
     * <code>
     *     $qb = $em->createQueryBuilder();
     *     $expr = $em->getExpressionBuilder();
     *     $qb->select('u')->from('User', 'u')
     *         ->where($expr->orX($expr->eq('u.id', 1), $expr->eq('u.id', 2)));
     * </code>
     *
     * @return Doctrine\ORM\Query\Expr
     */
    public function getExpressionBuilder()
    {
        if ($this->expressionBuilder === null) {
            $this->expressionBuilder = new Query\Expr;
        }
        return $this->expressionBuilder;
    }

    /**
     * Starts a transaction on the underlying database connection.
     *
     * @deprecated Use {@link getConnection}.beginTransaction().
     */
    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this EntityManager instance as an (optional) parameter.
     *
     * {@link flush} is invoked prior to transaction commit.
     *
     * If an exception occurs during execution of the function or flushing or transaction commit,
     * the transaction is rolled back, the EntityManager closed and the exception re-thrown.
     *
     * @param Closure $func The function to execute transactionally.
     */
    public function transactional(Closure $func)
    {
        $this->conn->beginTransaction();

        try {
            $return = $func($this);

            $this->flush();
            $this->conn->commit();

            return $return ?: true;
        } catch (Exception $e) {
            $this->close();
            $this->conn->rollback();

            throw $e;
        }
    }

    /**
     * Commits a transaction on the underlying database connection.
     *
     * @deprecated Use {@link getConnection}.commit().
     */
    public function commit()
    {
        $this->conn->commit();
    }

    /**
     * Performs a rollback on the underlying database connection.
     *
     * @deprecated Use {@link getConnection}.rollback().
     */
    public function rollback()
    {
        $this->conn->rollback();
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
     * @return Doctrine\ORM\Mapping\ClassMetadata
     * @internal Performance-sensitive method.
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * Creates a new Query object.
     *
     * @param string  The DQL string.
     * @return Doctrine\ORM\Query
     */
    public function createQuery($dql = "")
    {
        $query = new Query($this);
        if ( ! empty($dql)) {
            $query->setDql($dql);
        }
        return $query;
    }

    /**
     * Creates a Query from a named query.
     *
     * @param string $name
     * @return Doctrine\ORM\Query
     */
    public function createNamedQuery($name)
    {
        return $this->createQuery($this->config->getNamedQuery($name));
    }

    /**
     * Creates a native SQL query.
     *
     * @param string $sql
     * @param ResultSetMapping $rsm The ResultSetMapping to use.
     * @return NativeQuery
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        $query = new NativeQuery($this);
        $query->setSql($sql);
        $query->setResultSetMapping($rsm);
        return $query;
    }

    /**
     * Creates a NativeQuery from a named native query.
     *
     * @param string $name
     * @return Doctrine\ORM\NativeQuery
     */
    public function createNamedNativeQuery($name)
    {
        list($sql, $rsm) = $this->config->getNamedNativeQuery($name);
        return $this->createNativeQuery($sql, $rsm);
    }

    /**
     * Create a QueryBuilder instance
     *
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * @throws Doctrine\ORM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     */
    public function flush()
    {
        $this->errorIfClosed();
        $this->unitOfWork->commit();
    }

    /**
     * Finds an Entity by its identifier.
     *
     * This is just a convenient shortcut for getRepository($entityName)->find($id).
     *
     * @param string $entityName
     * @param mixed $identifier
     * @param int $lockMode
     * @param int $lockVersion
     * @return object
     */
    public function find($entityName, $identifier, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository($entityName)->find($identifier, $lockMode, $lockVersion);
    }

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed $identifier The entity identifier.
     * @return object The entity reference.
     */
    public function getReference($entityName, $identifier)
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($entityName, '\\'));

        // Check identity map first, if its already in there just return it.
        if ($entity = $this->unitOfWork->tryGetById($identifier, $class->rootEntityName)) {
            return ($entity instanceof $class->name) ? $entity : null;
        }
        if ($class->subClasses) {
            $entity = $this->find($entityName, $identifier);
        } else {
            if ( ! is_array($identifier)) {
                $identifier = array($class->identifier[0] => $identifier);
            }
            $entity = $this->proxyFactory->getProxy($class->name, $identifier);
            $this->unitOfWork->registerManaged($entity, $identifier, array());
        }

        return $entity;
    }

    /**
     * Gets a partial reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * The returned reference may be a partial object if the entity is not yet loaded/managed.
     * If it is a partial object it will not initialize the rest of the entity state on access.
     * Thus you can only ever safely access the identifier of an entity obtained through
     * this method.
     *
     * The use-cases for partial references involve maintaining bidirectional associations
     * without loading one side of the association or to update an entity without loading it.
     * Note, however, that in the latter case the original (persistent) entity data will
     * never be visible to the application (especially not event listeners) as it will
     * never be loaded in the first place.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed $identifier The entity identifier.
     * @return object The (partial) entity reference.
     */
    public function getPartialReference($entityName, $identifier)
    {
        $class = $this->metadataFactory->getMetadataFor(ltrim($entityName, '\\'));

        // Check identity map first, if its already in there just return it.
        if ($entity = $this->unitOfWork->tryGetById($identifier, $class->rootEntityName)) {
            return ($entity instanceof $class->name) ? $entity : null;
        }
        if ( ! is_array($identifier)) {
            $identifier = array($class->identifier[0] => $identifier);
        }

        $entity = $class->newInstance();
        $class->setIdentifierValues($entity, $identifier);
        $this->unitOfWork->registerManaged($entity, $identifier, array());
        $this->unitOfWork->markReadOnly($entity);

        return $entity;
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string $entityName
     */
    public function clear($entityName = null)
    {
        if ($entityName === null) {
            $this->unitOfWork->clear();
        } else {
            //TODO
            throw new ORMException("EntityManager#clear(\$entityName) not yet implemented.");
        }
    }

    /**
     * Closes the EntityManager. All entities that are currently managed
     * by this EntityManager become detached. The EntityManager may no longer
     * be used after it is closed.
     */
    public function close()
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
     * @param object $object The instance to make managed and persistent.
     */
    public function persist($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
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
     */
    public function remove($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        $this->unitOfWork->remove($entity);
    }

    /**
     * Refreshes the persistent state of an entity from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $entity The entity to refresh.
     */
    public function refresh($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
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
     */
    public function detach($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->unitOfWork->detach($entity);
    }

    /**
     * Merges the state of a detached entity into the persistence context
     * of this EntityManager and returns the managed copy of the entity.
     * The entity passed to merge will not become associated/managed with this EntityManager.
     *
     * @param object $entity The detached entity to merge into the persistence context.
     * @return object The managed copy of the entity.
     */
    public function merge($entity)
    {
        if ( ! is_object($entity)) {
            throw new \InvalidArgumentException(gettype($entity));
        }
        $this->errorIfClosed();
        return $this->unitOfWork->merge($entity);
    }

    /**
     * Creates a copy of the given entity. Can create a shallow or a deep copy.
     *
     * @param object $entity  The entity to copy.
     * @return object  The new entity.
     * @todo Implementation need. This is necessary since $e2 = clone $e1; throws an E_FATAL when access anything on $e:
     * Fatal error: Maximum function nesting level of '100' reached, aborting!
     */
    public function copy($entity, $deep = false)
    {
        throw new \BadMethodCallException("Not implemented.");
    }

    /**
     * Acquire a lock on the given entity.
     *
     * @param object $entity
     * @param int $lockMode
     * @param int $lockVersion
     * @throws OptimisticLockException
     * @throws PessimisticLockException
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->unitOfWork->lock($entity, $lockMode, $lockVersion);
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     * @return EntityRepository The repository class.
     */
    public function getRepository($entityName)
    {
        $entityName = ltrim($entityName, '\\');
        if (isset($this->repositories[$entityName])) {
            return $this->repositories[$entityName];
        }

        $metadata = $this->getClassMetadata($entityName);
        $customRepositoryClassName = $metadata->customRepositoryClassName;

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $metadata);
        } else {
            $repository = new EntityRepository($this, $metadata);
        }

        $this->repositories[$entityName] = $repository;

        return $repository;
    }

    /**
     * Determines whether an entity instance is managed in this EntityManager.
     *
     * @param object $entity
     * @return boolean TRUE if this EntityManager currently manages the given entity, FALSE otherwise.
     */
    public function contains($entity)
    {
        return $this->unitOfWork->isScheduledForInsert($entity) ||
               $this->unitOfWork->isInIdentityMap($entity) &&
               ! $this->unitOfWork->isScheduledForDelete($entity);
    }

    /**
     * Gets the EventManager used by the EntityManager.
     *
     * @return Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * Gets the Configuration used by the EntityManager.
     *
     * @return Doctrine\ORM\Configuration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Throws an exception if the EntityManager is closed or currently not active.
     *
     * @throws ORMException If the EntityManager is closed.
     */
    private function errorIfClosed()
    {
        if ($this->closed) {
            throw ORMException::entityManagerClosed();
        }
    }

    /**
     * Check if the Entity manager is open or closed.
     *
     * @return bool
     */
    public function isOpen()
    {
        return (!$this->closed);
    }

    /**
     * Gets the UnitOfWork used by the EntityManager to coordinate operations.
     *
     * @return Doctrine\ORM\UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * Gets a hydrator for the given hydration mode.
     *
     * This method caches the hydrator instances which is used for all queries that don't
     * selectively iterate over the result.
     *
     * @param int $hydrationMode
     * @return Doctrine\ORM\Internal\Hydration\AbstractHydrator
     */
    public function getHydrator($hydrationMode)
    {
        if ( ! isset($this->hydrators[$hydrationMode])) {
            $this->hydrators[$hydrationMode] = $this->newHydrator($hydrationMode);
        }

        return $this->hydrators[$hydrationMode];
    }

    /**
     * Create a new instance for the given hydration mode.
     *
     * @param  int $hydrationMode
     * @return Doctrine\ORM\Internal\Hydration\AbstractHydrator
     */
    public function newHydrator($hydrationMode)
    {
        switch ($hydrationMode) {
            case Query::HYDRATE_OBJECT:
                $hydrator = new Internal\Hydration\ObjectHydrator($this);
                break;
            case Query::HYDRATE_ARRAY:
                $hydrator = new Internal\Hydration\ArrayHydrator($this);
                break;
            case Query::HYDRATE_SCALAR:
                $hydrator = new Internal\Hydration\ScalarHydrator($this);
                break;
            case Query::HYDRATE_SINGLE_SCALAR:
                $hydrator = new Internal\Hydration\SingleScalarHydrator($this);
                break;
            case Query::HYDRATE_SIMPLEOBJECT:
                $hydrator = new Internal\Hydration\SimpleObjectHydrator($this);
                break;
            default:
                if ($class = $this->config->getCustomHydrationMode($hydrationMode)) {
                    $hydrator = new $class($this);
                    break;
                }
                throw ORMException::invalidHydrationMode($hydrationMode);
        }

        return $hydrator;
    }

    /**
     * Gets the proxy factory used by the EntityManager to create entity proxies.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function initializeObject($entity)
    {
        $this->unitOfWork->initializeObject($entity);
    }

    /**
     * Factory method to create EntityManager instances.
     *
     * @param mixed $conn An array with the connection parameters or an existing
     *      Connection instance.
     * @param Configuration $config The Configuration instance to use.
     * @param EventManager $eventManager The EventManager instance to use.
     * @return EntityManager The created EntityManager.
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, ($eventManager ?: new EventManager()));
        } else if ($conn instanceof Connection) {
            if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                 throw ORMException::mismatchedEventManager();
            }
        } else {
            throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new EntityManager($conn, $config, $conn->getEventManager());
    }
}
