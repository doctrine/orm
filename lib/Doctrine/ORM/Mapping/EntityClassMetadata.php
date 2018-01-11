<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * Class EntityClassMetadata
 *
 */
abstract class EntityClassMetadata extends ComponentMetadata
{
    /** @var string The name of the Entity */
    protected $entityName;

    /**
     * @var string|null The name of the custom repository class used for the entity class.
     */
    protected $customRepositoryClassName;

    /**
     * @var Property|null The field which is used for versioning in optimistic locking (if any).
     */
    protected $declaredVersion;

    /**
     * Whether this class describes the mapping of a read-only class.
     * That means it is never considered for change-tracking in the UnitOfWork.
     * It is a very helpful performance optimization for entities that are immutable,
     * either in your domain or through the relation database (coming from a view,
     * or a history table for example).
     *
     * @var bool
     */
    protected $readOnly = false;

    /**
     * List of all sub-classes (descendants) metadata.
     *
     * @var SubClassMetadata[]
     */
    protected $subClasses = [];

    /**
     * The named queries allowed to be called directly from Repository.
     *
     * @var string[]
     */
    protected $namedQueries = [];

    /**
     * READ-ONLY: The named native queries allowed to be called directly from Repository.
     *
     * A native SQL named query definition has the following structure:
     * <pre>
     * array(
     *     'name'               => <query name>,
     *     'query'              => <sql query>,
     *     'resultClass'        => <class of the result>,
     *     'resultSetMapping'   => <name of a SqlResultSetMapping>
     * )
     * </pre>
     *
     * @var string[][]
     */
    protected $namedNativeQueries = [];

    /**
     * READ-ONLY: The mappings of the results of native SQL queries.
     *
     * A native result mapping definition has the following structure:
     * <pre>
     * array(
     *     'name'               => <result name>,
     *     'entities'           => array(<entity result mapping>),
     *     'columns'            => array(<column result mapping>)
     * )
     * </pre>
     *
     * @var mixed[][]
     */
    protected $sqlResultSetMappings = [];

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var string[][]
     */
    protected $lifecycleCallbacks = [];

    /**
     * READ-ONLY: The registered entity listeners.
     *
     * @var string[][]
     */
    protected $entityListeners = [];

    /**
     * READ-ONLY: The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @var string[]
     */
    protected $identifier = [];

    /**
     * READ-ONLY: The primary table metadata.
     *
     * @var TableMetadata
     */
    protected $table;

    /**
     * MappedSuperClassMetadata constructor.
     */
    public function __construct(string $className, ClassMetadataBuildingContext $metadataBuildingContext)
    {
        parent::__construct($className);

        $this->entityName = $className;
    }

    public function getEntityName() : string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName) : void
    {
        $this->entityName = $entityName;
    }

    public function getCustomRepositoryClassName() : ?string
    {
        return $this->customRepositoryClassName;
    }

    public function setCustomRepositoryClassName(?string $customRepositoryClassName) : void
    {
        $this->customRepositoryClassName = $customRepositoryClassName;
    }

    public function getDeclaredVersion() : ?Property
    {
        return $this->declaredVersion;
    }

    public function setDeclaredVersion(Property $property) : void
    {
        $this->declaredVersion = $property;
    }

    public function getVersion() : ?Property
    {
        /** @var ComponentMetadata|null $parent */
        $parent  = $this->parent;
        $version = $this->declaredVersion;

        if ($parent && ! $version) {
            $version = $parent->getVersion();
        }

        return $version;
    }

    public function isVersioned() : bool
    {
        return $this->getVersion() !== null;
    }

    public function setReadOnly(bool $readOnly) : void
    {
        $this->readOnly = $readOnly;
    }

    public function isReadOnly() : bool
    {
        return $this->readOnly;
    }

    /**
     *
     * @throws MappingException
     */
    public function addSubClass(SubClassMetadata $subClassMetadata) : void
    {
        $superClassMetadata = $this->getSuperClass();

        while ($superClassMetadata !== null) {
            if ($superClassMetadata->entityName === $subClassMetadata->entityName) {
                throw new MappingException(
                    sprintf(
                        'Circular inheritance mapping detected: "%s" have itself as superclass when extending "%s".',
                        $subClassMetadata->entityName,
                        $superClassMetadata->entityName
                    )
                );
            }

            $superClassMetadata->subClasses[] = $subClassMetadata;

            $superClassMetadata = $superClassMetadata->parent;
        }

        $this->subClasses[] = $subClassMetadata;
    }

    public function hasSubClasses() : bool
    {
        return (bool) $this->subClasses;
    }

    public function getSubClassIterator() : \Iterator
    {
        $iterator = new \AppendIterator();

        foreach ($this->subClasses as $subClassMetadata) {
            $iterator->append($subClassMetadata->getSubClassIterator());
        }

        $iterator->append(new \ArrayIterator($this->subClasses));

        return $iterator;
    }

    /**
     * Adds a named query.
     *
     * @throws MappingException
     */
    public function addNamedQuery(string $name, string $dqlQuery) : void
    {
        if (isset($this->namedQueries[$name])) {
            throw MappingException::duplicateQueryMapping($this->entityName, $name);
        }

        $this->namedQueries[$name] = $dqlQuery;
    }

    /**
     * Gets a named query.
     *
     * @param string $queryName The query name.
     *
     * @throws MappingException
     */
    public function getNamedQuery($queryName) : string
    {
        if (! isset($this->namedQueries[$queryName])) {
            throw MappingException::queryNotFound($this->entityName, $queryName);
        }

        return $this->namedQueries[$queryName];
    }

    /**
     * Gets all named queries of the class.
     *
     * @return string[]
     */
    public function getNamedQueries() : array
    {
        return $this->namedQueries;
    }

    /**
     * Gets a named native query.
     *
     * @param string $queryName The native query name.
     *
     * @return string[]
     *
     * @throws MappingException
     *
     * @todo guilhermeblanco This should return an object instead
     */
    public function getNamedNativeQuery($queryName) : array
    {
        if (! isset($this->namedNativeQueries[$queryName])) {
            throw MappingException::queryNotFound($this->entityName, $queryName);
        }

        return $this->namedNativeQueries[$queryName];
    }

    /**
     * Gets all named native queries of the class.
     *
     * @return string[][]
     */
    public function getNamedNativeQueries() : array
    {
        return $this->namedNativeQueries;
    }

    /**
     * Gets the result set mapping.
     *
     * @param string $name The result set mapping name.
     *
     * @return mixed[]
     *
     * @throws MappingException
     *
     * @todo guilhermeblanco This should return an object instead
     */
    public function getSqlResultSetMapping($name) : array
    {
        if (! isset($this->sqlResultSetMappings[$name])) {
            throw MappingException::resultMappingNotFound($this->entityName, $name);
        }

        return $this->sqlResultSetMappings[$name];
    }

    /**
     * Gets all sql result set mappings of the class.
     *
     * @return mixed[][]
     */
    public function getSqlResultSetMappings() : array
    {
        return $this->sqlResultSetMappings;
    }

    /**
     * {@inheritdoc}
     */
    public function addDeclaredProperty(Property $property) : void
    {
        parent::addDeclaredProperty($property);

        if ($property instanceof VersionFieldMetadata) {
            $this->setDeclaredVersion($property);
        }
    }

    abstract public function getRootClass() : RootClassMetadata;
}
