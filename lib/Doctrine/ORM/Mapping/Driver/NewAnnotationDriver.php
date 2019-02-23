<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Factory;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;
use function array_diff;
use function array_filter;
use function array_intersect;
use function array_map;
use function array_merge;
use function class_exists;
use function constant;
use function count;
use function defined;
use function get_class;
use function in_array;
use function is_numeric;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

class NewAnnotationDriver implements MappingDriver
{
    /** @var int[] */
    static protected $entityAnnotationClasses = [
        Annotation\Entity::class           => 1,
        Annotation\MappedSuperclass::class => 2,
    ];

    /**
     * The Annotation reader.
     *
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * The file locator.
     *
     * @var FileLocator
     */
    protected $locator;

    /** @var Factory\NamingStrategy */
    protected $namingStrategy;

    /**
     * Cache for AnnotationDriver#getAllClassNames().
     *
     * @var string[]|null
     */
    private $classNames;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading docblock annotations.
     *
     * @param AnnotationReader       $reader         The AnnotationReader to use, duck-typed.
     * @param FileLocator            $locator        A FileLocator or one/multiple paths where mapping documents can be found.
     * @param Factory\NamingStrategy $namingStrategy The NamingStrategy to use.
     */
    public function __construct(AnnotationReader $reader, FileLocator $locator, Factory\NamingStrategy $namingStrategy)
    {
        $this->reader         = $reader;
        $this->locator        = $locator;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * {@inheritdoc}
     *
     * @return Mapping\ClassMetadata
     *
     * @throws Mapping\MappingException
     */
    public function loadMetadataForClass(
        string $className,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) {
        // IMPORTANT: We're handling $metadata as "parent" metadata here, while building the $className ClassMetadata.
        $reflectionClass = new ReflectionClass($className);

        // Evaluate annotations on class metadata
        $classAnnotations = $this->getClassAnnotations($reflectionClass);
        $classMetadata    = $this->convertClassAnnotationsToClassMetadata(
            $classAnnotations,
            $reflectionClass,
            $metadata
        );

        // Evaluate @Cache annotation
        if (isset($classAnnotations[Annotation\Cache::class])) {
            $cacheAnnot = $classAnnotations[Annotation\Cache::class];
            $cache      = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $classMetadata);

            $classMetadata->setCache($cache);
        }

        // Evaluate annotations on properties/fields
        /** @var ReflectionProperty $reflectionProperty */
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getClassName() !== $reflectionClass->getName()) {
                continue;
            }

            $propertyAnnotations = $this->getPropertyAnnotations($reflectionProperty);
            $property            = $this->convertReflectionPropertyAnnotationsToProperty(
                $reflectionProperty,
                $propertyAnnotations,
                $classMetadata
            );

            $classMetadata->addDeclaredProperty($property);
        }

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        $classNames = array_filter(
            $this->locator->getAllClassNames(null),
            function ($className) {
                return ! $this->isTransient($className);
            }
        );

        $this->classNames = $classNames;

        return $classNames;
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        $reflectionClass  = new ReflectionClass($className);
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $annotation) {
            if (isset(self::$entityAnnotationClasses[get_class($annotation)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @return Mapping\ClassMetadata|Mapping\ComponentMetadata
     *
     * @throws Mapping\MappingException
     */
    private function convertClassAnnotationsToClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $parent
    ) {
        switch (true) {
            case isset($classAnnotations[Annotation\Entity::class]):
                return $this->convertClassAnnotationsToEntityClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $parent
                );

                break;

            case isset($classAnnotations[Annotation\MappedSuperclass::class]):
                return $this->convertClassAnnotationsToMappedSuperClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $parent
                );

            case isset($classAnnotations[Annotation\Embeddable::class]):
                return $this->convertClassAnnotationsToEntityClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $parent
                );

            default:
                throw Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($reflectionClass->getName());
        }
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @return Mapping\ClassMetadata
     *
     * @throws Mapping\MappingException
     * @throws UnexpectedValueException
     */
    private function convertClassAnnotationsToEntityClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $parent
    ) {
        /** @var Annotation\Entity $entityAnnot */
        $entityAnnot   = $classAnnotations[Annotation\Entity::class];
        $classMetadata = new Mapping\ClassMetadata($reflectionClass->getName(), $parent);

        if ($entityAnnot->repositoryClass !== null) {
            $classMetadata->setCustomRepositoryClassName($entityAnnot->repositoryClass);
        }

        if ($entityAnnot->readOnly) {
            $classMetadata->asReadOnly();
        }

        // Evaluate @Table annotation
        if (isset($classAnnotations[Annotation\Table::class])) {
            /** @var Annotation\Table $tableAnnot */
            $tableAnnot = $classAnnotations[Annotation\Table::class];
            $table      = $this->convertTableAnnotationToTableMetadata($tableAnnot);

            $classMetadata->setTable($table);
        }

        // Evaluate @ChangeTrackingPolicy annotation
        if (isset($classAnnotations[Annotation\ChangeTrackingPolicy::class])) {
            /** @var Annotation\ChangeTrackingPolicy $changeTrackingAnnot */
            $changeTrackingAnnot = $classAnnotations[Annotation\ChangeTrackingPolicy::class];

            $classMetadata->setChangeTrackingPolicy(
                constant(sprintf('%s::%s', Mapping\ChangeTrackingPolicy::class, $changeTrackingAnnot->value))
            );
        }

        // Evaluate @EntityListeners annotation
        if (isset($classAnnotations[Annotation\EntityListeners::class])) {
            /** @var Annotation\EntityListeners $entityListenersAnnot */
            $entityListenersAnnot = $classAnnotations[Annotation\EntityListeners::class];

            foreach ($entityListenersAnnot->value as $listenerClassName) {
                if (! class_exists($listenerClassName)) {
                    throw Mapping\MappingException::entityListenerClassNotFound(
                        $listenerClassName,
                        $reflectionClass->getName()
                    );
                }

                $listenerClass = new ReflectionClass($listenerClassName);

                /** @var ReflectionMethod $method */
                foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    foreach ($this->getMethodCallbacks($method) as $callback) {
                        $classMetadata->addEntityListener($callback, $listenerClassName, $method->getName());
                    }
                }
            }
        }

        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAnnotations[Annotation\HasLifecycleCallbacks::class])) {
            /** @var ReflectionMethod $method */
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($this->getMethodCallbacks($method) as $callback) {
                    $classMetadata->addLifecycleCallback($method->getName(), $callback);
                }
            }
        }

        // Evaluate @InheritanceType annotation
        if (isset($classAnnotations[Annotation\InheritanceType::class])) {
            /** @var Annotation\InheritanceType $inheritanceTypeAnnot */
            $inheritanceTypeAnnot = $classAnnotations[Annotation\InheritanceType::class];

            $classMetadata->setInheritanceType(
                constant(sprintf('%s::%s', Mapping\InheritanceType::class, $inheritanceTypeAnnot->value))
            );

            if ($classMetadata->inheritanceType !== Mapping\InheritanceType::NONE) {
                $discriminatorColumn = new Mapping\DiscriminatorColumnMetadata();

                // Evaluate @DiscriminatorColumn annotation
                if (isset($classAnnotations[Annotation\DiscriminatorColumn::class])) {
                    /** @var Annotation\DiscriminatorColumn $discriminatorColumnAnnot */
                    $discriminatorColumnAnnot = $classAnnotations[Annotation\DiscriminatorColumn::class];

                    $discriminatorColumn->setColumnName($discriminatorColumnAnnot->name);

                    if (! empty($discriminatorColumnAnnot->columnDefinition)) {
                        $discriminatorColumn->setColumnDefinition($discriminatorColumnAnnot->columnDefinition);
                    }

                    if (! empty($discriminatorColumnAnnot->type)) {
                        $discriminatorColumn->setType(Type::getType($discriminatorColumnAnnot->type));
                    }

                    if (! empty($discriminatorColumnAnnot->length)) {
                        $discriminatorColumn->setLength($discriminatorColumnAnnot->length);
                    }
                }

                if (empty($discriminatorColumn->getColumnName())) {
                    throw Mapping\MappingException::nameIsMandatoryForDiscriminatorColumns($reflectionClass->getName());
                }

                $classMetadata->setDiscriminatorColumn($discriminatorColumn);

                // Evaluate @DiscriminatorMap annotation
                if (isset($classAnnotations[Annotation\DiscriminatorMap::class])) {
                    /** @var Annotation\DiscriminatorMap $discriminatorMapAnnotation */
                    $discriminatorMapAnnotation = $classAnnotations[Annotation\DiscriminatorMap::class];
                    $discriminatorMap           = $discriminatorMapAnnotation->value;

                    $classMetadata->setDiscriminatorMap($discriminatorMap);
                }
            }
        }

        return $classMetadata;
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @return Mapping\MappedSuperClassMetadata
     */
    private function convertClassAnnotationsToMappedSuperClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $parent
    ) {
        /** @var Annotation\MappedSuperclass $mappedSuperclassAnnot */
        $mappedSuperclassAnnot = $classAnnotations[Annotation\MappedSuperclass::class];
        $classMetadata         = new Mapping\MappedSuperClassMetadata($reflectionClass->getName(), $parent);

        if ($mappedSuperclassAnnot->repositoryClass !== null) {
            $classMetadata->setCustomRepositoryClassName($mappedSuperclassAnnot->repositoryClass);
        }

        return $classMetadata;
    }

    /**
     * Parse the given Table as TableMetadata
     *
     * @return Mapping\TableMetadata
     */
    private function convertTableAnnotationToTableMetadata(Annotation\Table $tableAnnot)
    {
        $table = new Mapping\TableMetadata();

        if (! empty($tableAnnot->name)) {
            $table->setName($tableAnnot->name);
        }

        if (! empty($tableAnnot->schema)) {
            $table->setSchema($tableAnnot->schema);
        }

        foreach ($tableAnnot->options as $optionName => $optionValue) {
            $table->addOption($optionName, $optionValue);
        }

        foreach ($tableAnnot->indexes as $indexAnnot) {
            $table->addIndex([
                'name'    => $indexAnnot->name,
                'columns' => $indexAnnot->columns,
                'unique'  => $indexAnnot->unique,
                'options' => $indexAnnot->options,
                'flags'   => $indexAnnot->flags,
            ]);
        }

        foreach ($tableAnnot->uniqueConstraints as $uniqueConstraintAnnot) {
            $table->addUniqueConstraint([
                'name'    => $uniqueConstraintAnnot->name,
                'columns' => $uniqueConstraintAnnot->columns,
                'options' => $uniqueConstraintAnnot->options,
                'flags'   => $uniqueConstraintAnnot->flags,
            ]);
        }

        return $table;
    }

    /**
     * Parse the given Cache as CacheMetadata
     *
     * @param string|null $fieldName
     *
     * @return Mapping\CacheMetadata
     */
    private function convertCacheAnnotationToCacheMetadata(
        Annotation\Cache $cacheAnnot,
        Mapping\ClassMetadata $metadata,
        $fieldName = null
    ) {
        $usage         = constant(sprintf('%s::%s', Mapping\CacheUsage::class, $cacheAnnot->usage));
        $baseRegion    = strtolower(str_replace('\\', '_', $metadata->getRootClassName()));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');

        return new Mapping\CacheMetadata($usage, $cacheAnnot->region ?: $defaultRegion);
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\Property
     *
     * @throws Mapping\MappingException
     */
    private function convertReflectionPropertyAnnotationsToProperty(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        // Field can only be annotated with one of:
        // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany, @Embedded
        switch (true) {
            case isset($propertyAnnotations[Annotation\Column::class]):
                return $this->convertReflectionPropertyToFieldMetadata(
                    $reflectionProperty,
                    $propertyAnnotations,
                    $classMetadata
                );

            case isset($propertyAnnotations[Annotation\OneToOne::class]):
                return $this->convertReflectionPropertyToOneToOneAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations,
                    $classMetadata
                );

            case isset($propertyAnnotations[Annotation\ManyToOne::class]):
                return $this->convertReflectionPropertyToManyToOneAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations,
                    $classMetadata
                );

            case isset($propertyAnnotations[Annotation\OneToMany::class]):
                return $this->convertReflectionPropertyToOneToManyAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations,
                    $classMetadata
                );

            case isset($propertyAnnotations[Annotation\ManyToMany::class]):
                return $this->convertReflectionPropertyToManyToManyAssociationMetadata(
                    $reflectionProperty,
                    $propertyAnnotations,
                    $classMetadata
                );

            case isset($propertyAnnotations[Annotation\Embedded::class]):
                // @todo guilhermeblanco Implement later... =)
                break;

            default:
                return new Mapping\TransientMetadata($reflectionProperty->getName());
        }
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\FieldMetadata
     *
     * @throws Mapping\MappingException
     */
    private function convertReflectionPropertyToFieldMetadata(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $className    = $classMetadata->getClassName();
        $fieldName    = $reflectionProperty->getName();
        $columnAnnot  = $propertyAnnotations[Annotation\Column::class];
        $isVersioned  = isset($propertyAnnotations[Annotation\Version::class]);
        $isPrimaryKey = isset($propertyAnnotations[Annotation\Id::class]);

        if ($columnAnnot->type === null) {
            throw Mapping\MappingException::propertyTypeIsRequired($className, $fieldName);
        }

        if ($isVersioned && $isPrimaryKey) {
            throw Mapping\MappingException::cannotVersionIdField($className, $fieldName);
        }

        $columnName = empty($columnAnnot->name)
            ? $this->namingStrategy->propertyToColumnName($fieldName, $className)
            : $columnAnnot->name;

        $fieldMetadata = $isVersioned
            ? new Mapping\VersionFieldMetadata($fieldName)
            : new Mapping\FieldMetadata($fieldName);

        $fieldMetadata->setType(Type::getType($columnAnnot->type));
        $fieldMetadata->setColumnName($columnName);
        $fieldMetadata->setScale($columnAnnot->scale);
        $fieldMetadata->setPrecision($columnAnnot->precision);
        $fieldMetadata->setNullable($columnAnnot->nullable);
        $fieldMetadata->setUnique($columnAnnot->unique);

        // Check for Id
        if ($isPrimaryKey) {
            if ($fieldMetadata->getType()->canRequireSQLConversion()) {
                throw Mapping\MappingException::sqlConversionNotAllowedForPrimaryKeyProperties($className, $fieldMetadata);
            }

            $fieldMetadata->setPrimaryKey(true);
        }

        if (! empty($columnAnnot->columnDefinition)) {
            $fieldMetadata->setColumnDefinition($columnAnnot->columnDefinition);
        }

        if (! empty($columnAnnot->length)) {
            $fieldMetadata->setLength($columnAnnot->length);
        }

        // Assign default options
        $customOptions  = $columnAnnot->options ?? [];
        $defaultOptions = [];

        if ($isVersioned) {
            switch ($fieldMetadata->getTypeName()) {
                case 'integer':
                case 'bigint':
                case 'smallint':
                    $defaultOptions['default'] = 1;
                    break;

                case 'datetime':
                    $defaultOptions['default'] = 'CURRENT_TIMESTAMP';
                    break;

                default:
                    if (! isset($customOptions['default'])) {
                        throw Mapping\MappingException::unsupportedOptimisticLockingType($fieldMetadata->getType());
                    }
            }
        }

        $fieldMetadata->setOptions(array_merge($defaultOptions, $customOptions));

        return $fieldMetadata;
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\OneToOneAssociationMetadata
     */
    private function convertReflectionPropertyToOneToOneAssociationMetadata(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $className     = $classMetadata->getClassName();
        $fieldName     = $reflectionProperty->getName();
        $oneToOneAnnot = $propertyAnnotations[Annotation\OneToOne::class];

        if ($oneToOneAnnot->targetEntity === null) {
            throw Mapping\MappingException::missingTargetEntity($fieldName);
        }

        $assocMetadata = new Mapping\OneToOneAssociationMetadata($fieldName);
        $targetEntity  = $oneToOneAnnot->targetEntity;

        $assocMetadata->setSourceEntity($className);
        $assocMetadata->setTargetEntity($targetEntity);
        $assocMetadata->setCascade($this->getCascade($className, $fieldName, $oneToOneAnnot->cascade));
        $assocMetadata->setOrphanRemoval($oneToOneAnnot->orphanRemoval);
        $assocMetadata->setFetchMode($this->getFetchMode($className, $oneToOneAnnot->fetch));

        if (! empty($oneToOneAnnot->mappedBy)) {
            $assocMetadata->setMappedBy($oneToOneAnnot->mappedBy);
        }

        if (! empty($oneToOneAnnot->inversedBy)) {
            $assocMetadata->setInversedBy($oneToOneAnnot->inversedBy);
        }

        // Check for Id
        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $assocMetadata->setPrimaryKey(true);
        }

        // Check for Cache
        if (isset($propertyAnnotations[Annotation\Cache::class])) {
            $cacheAnnot    = $propertyAnnotations[Annotation\Cache::class];
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $classMetadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        // Check for JoinColumn/JoinColumns annotations
        switch (true) {
            case isset($propertyAnnotations[Annotation\JoinColumn::class]):
                $joinColumnAnnot = $propertyAnnotations[Annotation\JoinColumn::class];
                $joinColumn      = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                    $reflectionProperty,
                    $joinColumnAnnot,
                    $classMetadata
                );

                $assocMetadata->addJoinColumn($joinColumn);

                break;

            case isset($propertyAnnotations[Annotation\JoinColumns::class]):
                $joinColumnsAnnot = $propertyAnnotations[Annotation\JoinColumns::class];

                foreach ($joinColumnsAnnot->value as $joinColumnAnnot) {
                    $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                        $reflectionProperty,
                        $joinColumnAnnot,
                        $classMetadata
                    );

                    $assocMetadata->addJoinColumn($joinColumn);
                }

                break;
        }

        return $assocMetadata;
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\ManyToOneAssociationMetadata
     */
    private function convertReflectionPropertyToManyToOneAssociationMetadata(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $className      = $classMetadata->getClassName();
        $fieldName      = $reflectionProperty->getName();
        $manyToOneAnnot = $propertyAnnotations[Annotation\ManyToOne::class];

        if ($manyToOneAnnot->targetEntity === null) {
            throw Mapping\MappingException::missingTargetEntity($fieldName);
        }

        $assocMetadata = new Mapping\ManyToOneAssociationMetadata($fieldName);
        $targetEntity  = $manyToOneAnnot->targetEntity;

        $assocMetadata->setSourceEntity($className);
        $assocMetadata->setTargetEntity($targetEntity);
        $assocMetadata->setCascade($this->getCascade($className, $fieldName, $manyToOneAnnot->cascade));
        $assocMetadata->setFetchMode($this->getFetchMode($className, $manyToOneAnnot->fetch));

        if (! empty($manyToOneAnnot->inversedBy)) {
            $assocMetadata->setInversedBy($manyToOneAnnot->inversedBy);
        }

        // Check for Id
        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $assocMetadata->setPrimaryKey(true);
        }

        // Check for Cache
        if (isset($propertyAnnotations[Annotation\Cache::class])) {
            $cacheAnnot    = $propertyAnnotations[Annotation\Cache::class];
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $classMetadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        // Check for JoinColumn/JoinColumns annotations
        switch (true) {
            case isset($propertyAnnotations[Annotation\JoinColumn::class]):
                $joinColumnAnnot = $propertyAnnotations[Annotation\JoinColumn::class];
                $joinColumn      = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                    $reflectionProperty,
                    $joinColumnAnnot,
                    $classMetadata
                );

                $assocMetadata->addJoinColumn($joinColumn);

                break;

            case isset($propertyAnnotations[Annotation\JoinColumns::class]):
                $joinColumnsAnnot = $propertyAnnotations[Annotation\JoinColumns::class];

                foreach ($joinColumnsAnnot->value as $joinColumnAnnot) {
                    $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                        $reflectionProperty,
                        $joinColumnAnnot,
                        $classMetadata
                    );

                    $assocMetadata->addJoinColumn($joinColumn);
                }

                break;
        }

        return $assocMetadata;
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\OneToManyAssociationMetadata
     */
    private function convertReflectionPropertyToOneToManyAssociationMetadata(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $className      = $classMetadata->getClassName();
        $fieldName      = $reflectionProperty->getName();
        $oneToManyAnnot = $propertyAnnotations[Annotation\OneToMany::class];

        if ($oneToManyAnnot->targetEntity === null) {
            throw Mapping\MappingException::missingTargetEntity($fieldName);
        }

        $assocMetadata = new Mapping\OneToManyAssociationMetadata($fieldName);
        $targetEntity  = $oneToManyAnnot->targetEntity;

        $assocMetadata->setSourceEntity($className);
        $assocMetadata->setTargetEntity($targetEntity);
        $assocMetadata->setCascade($this->getCascade($className, $fieldName, $oneToManyAnnot->cascade));
        $assocMetadata->setOrphanRemoval($oneToManyAnnot->orphanRemoval);
        $assocMetadata->setFetchMode($this->getFetchMode($className, $oneToManyAnnot->fetch));

        if (! empty($oneToManyAnnot->mappedBy)) {
            $assocMetadata->setMappedBy($oneToManyAnnot->mappedBy);
        }

        if (! empty($oneToManyAnnot->indexBy)) {
            $assocMetadata->setIndexedBy($oneToManyAnnot->indexBy);
        }

        // Check for OrderBy
        if (isset($propertyAnnotations[Annotation\OrderBy::class])) {
            $orderByAnnot = $propertyAnnotations[Annotation\OrderBy::class];

            $assocMetadata->setOrderBy($orderByAnnot->value);
        }

        // Check for Id
        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $assocMetadata->setPrimaryKey(true);
        }

        // Check for Cache
        if (isset($propertyAnnotations[Annotation\Cache::class])) {
            $cacheAnnot    = $propertyAnnotations[Annotation\Cache::class];
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $classMetadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        return $assocMetadata;
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @return Mapping\ManyToManyAssociationMetadata
     */
    private function convertReflectionPropertyToManyToManyAssociationMetadata(
        ReflectionProperty $reflectionProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $classMetadata
    ) {
        $className       = $classMetadata->getClassName();
        $fieldName       = $reflectionProperty->getName();
        $manyToManyAnnot = $propertyAnnotations[Annotation\ManyToMany::class];

        if ($manyToManyAnnot->targetEntity === null) {
            throw Mapping\MappingException::missingTargetEntity($fieldName);
        }

        $assocMetadata = new Mapping\ManyToManyAssociationMetadata($fieldName);
        $targetEntity  = $manyToManyAnnot->targetEntity;

        $assocMetadata->setSourceEntity($className);
        $assocMetadata->setTargetEntity($targetEntity);
        $assocMetadata->setCascade($this->getCascade($className, $fieldName, $manyToManyAnnot->cascade));
        $assocMetadata->setOrphanRemoval($manyToManyAnnot->orphanRemoval);
        $assocMetadata->setFetchMode($this->getFetchMode($className, $manyToManyAnnot->fetch));

        if (! empty($manyToManyAnnot->mappedBy)) {
            $assocMetadata->setMappedBy($manyToManyAnnot->mappedBy);
        }

        if (! empty($manyToManyAnnot->inversedBy)) {
            $assocMetadata->setInversedBy($manyToManyAnnot->inversedBy);
        }

        if (! empty($manyToManyAnnot->indexBy)) {
            $assocMetadata->setIndexedBy($manyToManyAnnot->indexBy);
        }

        // Check for JoinTable
        if (isset($propertyAnnotations[Annotation\JoinTable::class])) {
            $joinTableAnnot    = $propertyAnnotations[Annotation\JoinTable::class];
            $joinTableMetadata = $this->convertJoinTableAnnotationToJoinTableMetadata(
                $reflectionProperty,
                $joinTableAnnot,
                $classMetadata
            );

            $assocMetadata->setJoinTable($joinTableMetadata);
        }

        // Check for OrderBy
        if (isset($propertyAnnotations[Annotation\OrderBy::class])) {
            $orderByAnnot = $propertyAnnotations[Annotation\OrderBy::class];

            $assocMetadata->setOrderBy($orderByAnnot->value);
        }

        // Check for Id
        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $assocMetadata->setPrimaryKey(true);
        }

        // Check for Cache
        if (isset($propertyAnnotations[Annotation\Cache::class])) {
            $cacheAnnot    = $propertyAnnotations[Annotation\Cache::class];
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $classMetadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        return $assocMetadata;
    }

    /**
     * Parse the given JoinTable as JoinTableMetadata
     *
     * @return Mapping\JoinTableMetadata
     */
    private function convertJoinTableAnnotationToJoinTableMetadata(
        ReflectionProperty $reflectionProperty,
        Annotation\JoinTable $joinTableAnnot,
        Mapping\ClassMetadata $classMetadata
    ) {
        $joinTable = new Mapping\JoinTableMetadata();

        if (! empty($joinTableAnnot->name)) {
            $joinTable->setName($joinTableAnnot->name);
        }

        if (! empty($joinTableAnnot->schema)) {
            $joinTable->setSchema($joinTableAnnot->schema);
        }

        foreach ($joinTableAnnot->joinColumns as $joinColumnAnnot) {
            $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                $reflectionProperty,
                $joinColumnAnnot,
                $classMetadata
            );

            $joinTable->addJoinColumn($joinColumn);
        }

        foreach ($joinTableAnnot->inverseJoinColumns as $joinColumnAnnot) {
            $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata(
                $reflectionProperty,
                $joinColumnAnnot,
                $classMetadata
            );

            $joinTable->addInverseJoinColumn($joinColumn);
        }

        return $joinTable;
    }

    /**
     * Parse the given JoinColumn as JoinColumnMetadata
     *
     * @return Mapping\JoinColumnMetadata
     */
    private function convertJoinColumnAnnotationToJoinColumnMetadata(
        ReflectionProperty $reflectionProperty,
        Annotation\JoinColumn $joinColumnAnnot,
        Mapping\ClassMetadata $classMetadata
    ) {
        $fieldName            = $reflectionProperty->getName();
        $joinColumn           = new Mapping\JoinColumnMetadata();
        $columnName           = empty($joinColumnAnnot->name)
            ? $this->namingStrategy->propertyToColumnName($fieldName, $classMetadata->getClassName())
            : $joinColumnAnnot->name;
        $referencedColumnName = empty($joinColumnAnnot->referencedColumnName)
            ? $this->namingStrategy->referenceColumnName()
            : $joinColumnAnnot->referencedColumnName;

        $joinColumn->setColumnName($columnName);
        $joinColumn->setReferencedColumnName($referencedColumnName);
        $joinColumn->setNullable($joinColumnAnnot->nullable);
        $joinColumn->setUnique($joinColumnAnnot->unique);

        if (! empty($joinColumnAnnot->fieldName)) {
            $joinColumn->setAliasedName($joinColumnAnnot->fieldName);
        }

        if (! empty($joinColumnAnnot->columnDefinition)) {
            $joinColumn->setColumnDefinition($joinColumnAnnot->columnDefinition);
        }

        if ($joinColumnAnnot->onDelete) {
            $joinColumn->setOnDelete(strtoupper($joinColumnAnnot->onDelete));
        }

        return $joinColumn;
    }

    /**
     * Parses the given method.
     *
     * @return string[]
     */
    private function getMethodCallbacks(ReflectionMethod $method)
    {
        $annotations = $this->getMethodAnnotations($method);
        $events      = [
            Events::prePersist  => Annotation\PrePersist::class,
            Events::postPersist => Annotation\PostPersist::class,
            Events::preUpdate   => Annotation\PreUpdate::class,
            Events::postUpdate  => Annotation\PostUpdate::class,
            Events::preRemove   => Annotation\PreRemove::class,
            Events::postRemove  => Annotation\PostRemove::class,
            Events::postLoad    => Annotation\PostLoad::class,
            Events::preFlush    => Annotation\PreFlush::class,
        ];

        // Check for callbacks
        $callbacks = [];

        foreach ($events as $eventName => $annotationClassName) {
            if (isset($annotations[$annotationClassName]) || $method->getName() === $eventName) {
                $callbacks[] = $eventName;
            }
        }

        return $callbacks;
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param string $className The class name.
     * @param string $fetchMode The fetch mode.
     *
     * @return int The fetch mode as defined in ClassMetadata.
     *
     * @throws Mapping\MappingException If the fetch mode is not valid.
     */
    private function getFetchMode($className, $fetchMode)
    {
        $fetchModeConstant = sprintf('%s::%s', Mapping\FetchMode::class, $fetchMode);

        if (! defined($fetchModeConstant)) {
            throw Mapping\MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant($fetchModeConstant);
    }

    /**
     * @param string   $className        The class name.
     * @param string   $fieldName        The field name.
     * @param string[] $originalCascades The original unprocessed field cascades.
     *
     * @return string[] The processed field cascades.
     *
     * @throws Mapping\MappingException If a cascade option is not valid.
     */
    private function getCascade(string $className, string $fieldName, array $originalCascades)
    {
        $cascadeTypes = ['remove', 'persist', 'refresh'];
        $cascades     = array_map('strtolower', $originalCascades);

        if (in_array('all', $cascades, true)) {
            $cascades = $cascadeTypes;
        }

        if (count($cascades) !== count(array_intersect($cascades, $cascadeTypes))) {
            $diffCascades = array_diff($cascades, array_intersect($cascades, $cascadeTypes));

            throw Mapping\MappingException::invalidCascadeOption($diffCascades, $className, $fieldName);
        }

        return $cascades;
    }

    /**
     * @return Annotation\Annotation[]
     */
    private function getClassAnnotations(ReflectionClass $reflectionClass)
    {
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $classAnnotations[get_class($annot)] = $annot;
        }

        return $classAnnotations;
    }

    /**
     * @return Annotation\Annotation[]
     */
    private function getPropertyAnnotations(ReflectionProperty $reflectionProperty)
    {
        $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);

        foreach ($propertyAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $propertyAnnotations[get_class($annot)] = $annot;
        }

        return $propertyAnnotations;
    }

    /**
     * @return Annotation\Annotation[]
     */
    private function getMethodAnnotations(ReflectionMethod $reflectionMethod)
    {
        $methodAnnotations = $this->reader->getMethodAnnotations($reflectionMethod);

        foreach ($methodAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $methodAnnotations[get_class($annot)] = $annot;
        }

        return $methodAnnotations;
    }
}
