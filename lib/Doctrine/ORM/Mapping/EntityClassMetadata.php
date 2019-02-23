<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use AppendIterator;
use ArrayIterator;
use Iterator;
use function sprintf;

abstract class EntityClassMetadata extends ComponentMetadata
{
    /** @var string The name of the Entity */
    protected $entityName;

    /** @var string|null The name of the custom repository class used for the entity class. */
    protected $customRepositoryClassName;

    /** @var Property|null The field which is used for versioning in optimistic locking (if any). */
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

    public function __construct(string $className, ClassMetadataBuildingContext $metadataBuildingContext)
    {
        parent::__construct($className, $metadataBuildingContext);

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
     * @throws MappingException
     */
    public function addSubClass(SubClassMetadata $subClassMetadata) : void
    {
        /** @var EntityClassMetadata|null $superClassMetadata */
        $superClassMetadata = $this->getParent();

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

    public function getSubClassIterator() : Iterator
    {
        $iterator = new AppendIterator();

        foreach ($this->subClasses as $subClassMetadata) {
            $iterator->append($subClassMetadata->getSubClassIterator());
        }

        $iterator->append(new ArrayIterator($this->subClasses));

        return $iterator;
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
