<?php /** @noinspection ALL */

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Cache\Exception\CacheException;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Builder;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RegexIterator;
use RuntimeException;
use UnexpectedValueException;
use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_unique;
use function class_exists;
use function constant;
use function count;
use function defined;
use function get_class;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function is_numeric;
use function preg_match;
use function preg_quote;
use function realpath;
use function sprintf;
use function str_replace;
use function strpos;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 */
class AnnotationDriver implements MappingDriver
{
    /** @var int[] */
    protected $entityAnnotationClasses = [
        Annotation\Entity::class           => 1,
        Annotation\MappedSuperclass::class => 2,
    ];

    /**
     * The AnnotationReader.
     *
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var string[]
     */
    protected $paths = [];

    /**
     * The paths excluded from path where to look for mapping files.
     *
     * @var string[]
     */
    protected $excludePaths = [];

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $fileExtension = '.php';

    /**
     * Cache for AnnotationDriver#getAllClassNames().
     *
     * @var string[]|null
     */
    protected $classNames;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param Reader               $reader The AnnotationReader to use, duck-typed.
     * @param string|string[]|null $paths  One or multiple paths where mapping classes can be found.
     */
    public function __construct(Reader $reader, $paths = null)
    {
        $this->reader = $reader;

        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    /**
     * Appends lookup paths to metadata driver.
     *
     * @param string[] $paths
     */
    public function addPaths(array $paths)
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }

    /**
     * Retrieves the defined metadata lookup paths.
     *
     * @return string[]
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Append exclude lookup paths to metadata driver.
     *
     * @param string[] $paths
     */
    public function addExcludePaths(array $paths)
    {
        $this->excludePaths = array_unique(array_merge($this->excludePaths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup exclude paths.
     *
     * @return string[]
     */
    public function getExcludePaths()
    {
        return $this->excludePaths;
    }

    /**
     * Retrieve the current annotation reader
     *
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Gets the file extension used to look for mapping files under.
     *
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * Sets the file extension used to look for mapping files under.
     *
     * @param string $fileExtension The file extension to set.
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * Returns whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     *
     * A class is non-transient if it is annotated with an annotation
     * from the {@see AnnotationDriver::entityAnnotationClasses}.
     *
     * @param string $className
     *
     * @throws ReflectionException
     */
    public function isTransient($className) : bool
    {
        $classAnnotations = $this->reader->getClassAnnotations(new ReflectionClass($className));

        foreach ($classAnnotations as $annotation) {
            if (isset($this->entityAnnotationClasses[get_class($annotation)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function getAllClassNames() : array
    {
        if ($this->classNames !== null) {
            return $this->classNames;
        }

        if (! $this->paths) {
            throw Mapping\MappingException::pathRequired();
        }

        $classes       = [];
        $includedFiles = [];

        foreach ($this->paths as $path) {
            if (! is_dir($path)) {
                throw Mapping\MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . preg_quote($this->fileExtension) . '$/i',
                RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (! preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                foreach ($this->excludePaths as $excludePath) {
                    $exclude = str_replace('\\', '/', realpath($excludePath));
                    $current = str_replace('\\', '/', $sourceFile);

                    if (strpos($current, $exclude) !== false) {
                        continue 2;
                    }
                }

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $reflectionClass = new ReflectionClass($className);
            $sourceFile      = $reflectionClass->getFileName();

            if (in_array($sourceFile, $includedFiles, true) && ! $this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->classNames = $classes;

        return $classes;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CacheException
     * @throws Mapping\MappingException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function loadMetadataForClass(
        string $className,
        ?Mapping\ComponentMetadata $parent,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : Mapping\ComponentMetadata {
        $reflectionClass  = new ReflectionClass($className);
        $metadata         = new Mapping\ClassMetadata($className, $parent, $metadataBuildingContext);
        $classAnnotations = $this->getClassAnnotations($reflectionClass);
        $classMetadata    = $this->convertClassAnnotationsToClassMetadata(
            $classAnnotations,
            $reflectionClass,
            $metadata,
            $metadataBuildingContext
        );

        // Evaluate @Cache annotation
        if (isset($classAnnotations[Annotation\Cache::class])) {
            $cacheBuilder = new Builder\CacheMetadataBuilder($metadataBuildingContext);

            $cacheBuilder
                ->withComponentMetadata($metadata)
                ->withCacheAnnotation($classAnnotations[Annotation\Cache::class]);

            $metadata->setCache($cacheBuilder->build());
        }

        // Evaluate annotations on properties/fields
        /** @var ReflectionProperty $reflProperty */
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
                continue;
            }

            $propertyAnnotations = $this->getPropertyAnnotations($reflectionProperty);
            $property            = $this->convertPropertyAnnotationsToProperty(
                $propertyAnnotations,
                $reflectionProperty,
                $classMetadata,
                $metadataBuildingContext
            );

            if ($classMetadata->isMappedSuperclass &&
                $property instanceof Mapping\ToManyAssociationMetadata &&
                ! $property->isOwningSide()) {
                throw Mapping\MappingException::illegalToManyAssociationOnMappedSuperclass(
                    $classMetadata->getClassName(),
                    $property->getName()
                );
            }

            $metadata->addProperty($property);
        }

        $this->attachPropertyOverrides($classAnnotations, $reflectionClass, $metadata, $metadataBuildingContext);

        return $classMetadata;
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @throws Mapping\MappingException
     * @throws UnexpectedValueException
     * @throws ReflectionException
     */
    private function convertClassAnnotationsToClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : Mapping\ClassMetadata {
        switch (true) {
            case isset($classAnnotations[Annotation\Entity::class]):
                return $this->convertClassAnnotationsToEntityClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $metadata,
                    $metadataBuildingContext
                );

                break;

            case isset($classAnnotations[Annotation\MappedSuperclass::class]):
                return $this->convertClassAnnotationsToMappedSuperClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $metadata
                );
            case isset($classAnnotations[Annotation\Embeddable::class]):
                return $this->convertClassAnnotationsToEmbeddableClassMetadata(
                    $classAnnotations,
                    $reflectionClass,
                    $metadata
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
     * @throws ReflectionException
     * @throws UnexpectedValueException
     */
    private function convertClassAnnotationsToEntityClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) {
        /** @var Annotation\Entity $entityAnnot */
        $entityAnnot = $classAnnotations[Annotation\Entity::class];

        if ($entityAnnot->repositoryClass !== null) {
            $metadata->setCustomRepositoryClassName($entityAnnot->repositoryClass);
        }

        if ($entityAnnot->readOnly) {
            $metadata->asReadOnly();
        }

        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;

        // Process table information
        $parent = $metadata->getParent();

        if ($parent && $parent->inheritanceType === Mapping\InheritanceType::SINGLE_TABLE) {
            // Handle the case where a middle mapped super class inherits from a single table inheritance tree.
            do {
                if (! $parent->isMappedSuperclass) {
                    $metadata->setTable($parent->table);

                    break;
                }

                $parent = $parent->getParent();
            } while ($parent !== null);
        } else {
            $tableBuilder = new Builder\TableMetadataBuilder($metadataBuildingContext);

            $tableBuilder
                ->withEntityClassMetadata($metadata)
                ->withTableAnnotation($classAnnotations[Annotation\Table::class] ?? null);

            $metadata->setTable($tableBuilder->build());
        }

        // Evaluate @ChangeTrackingPolicy annotation
        if (isset($classAnnotations[Annotation\ChangeTrackingPolicy::class])) {
            $changeTrackingAnnot = $classAnnotations[Annotation\ChangeTrackingPolicy::class];

            $metadata->setChangeTrackingPolicy(
                constant(sprintf('%s::%s', Mapping\ChangeTrackingPolicy::class, $changeTrackingAnnot->value))
            );
        }

        // Evaluate @InheritanceType annotation
        if (isset($classAnnotations[Annotation\InheritanceType::class])) {
            $inheritanceTypeAnnot = $classAnnotations[Annotation\InheritanceType::class];

            $metadata->setInheritanceType(
                constant(sprintf('%s::%s', Mapping\InheritanceType::class, $inheritanceTypeAnnot->value))
            );

            if ($metadata->inheritanceType !== Mapping\InheritanceType::NONE) {
                $discriminatorColumnBuilder = new Builder\DiscriminatorColumnMetadataBuilder($metadataBuildingContext);

                $discriminatorColumnBuilder
                    ->withComponentMetadata($metadata)
                    ->withDiscriminatorColumnAnnotation($classAnnotations[Annotation\DiscriminatorColumn::class] ?? null);

                $metadata->setDiscriminatorColumn($discriminatorColumnBuilder->build());

                // Evaluate DiscriminatorMap annotation
                if (isset($classAnnotations[Annotation\DiscriminatorMap::class])) {
                    $discriminatorMapAnnotation = $classAnnotations[Annotation\DiscriminatorMap::class];
                    $discriminatorMap           = $discriminatorMapAnnotation->value;

                    $metadata->setDiscriminatorMap($discriminatorMap);
                }
            }
        }

        $this->attachLifecycleCallbacks($classAnnotations, $reflectionClass, $metadata);
        $this->attachEntityListeners($classAnnotations, $metadata);

        return $metadata;
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @throws Mapping\MappingException
     * @throws ReflectionException
     */
    private function convertClassAnnotationsToMappedSuperClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata
    ) : Mapping\ClassMetadata {
        /** @var Annotation\MappedSuperclass $mappedSuperclassAnnot */
        $mappedSuperclassAnnot = $classAnnotations[Annotation\MappedSuperclass::class];

        if ($mappedSuperclassAnnot->repositoryClass !== null) {
            $metadata->setCustomRepositoryClassName($mappedSuperclassAnnot->repositoryClass);
        }

        $metadata->isMappedSuperclass = true;
        $metadata->isEmbeddedClass    = false;

        $this->attachLifecycleCallbacks($classAnnotations, $reflectionClass, $metadata);
        $this->attachEntityListeners($classAnnotations, $metadata);

        return $metadata;
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     */
    private function convertClassAnnotationsToEmbeddableClassMetadata(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata
    ) : Mapping\ClassMetadata {
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = true;

        return $metadata;
    }

    /**
     * @param Annotation\Annotation[] $propertyAnnotations
     *
     * @todo guilhermeblanco Remove nullable typehint once embeddables are back
     */
    private function convertPropertyAnnotationsToProperty(
        array $propertyAnnotations,
        ReflectionProperty $reflectionProperty,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : ?Mapping\Property {
        switch (true) {
            case isset($propertyAnnotations[Annotation\Column::class]):
                $fieldBuilder  = new Builder\FieldMetadataBuilder($metadataBuildingContext);
                $fieldMetadata = $fieldBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->withColumnAnnotation($propertyAnnotations[Annotation\Column::class])
                    ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                    ->withVersionAnnotation($propertyAnnotations[Annotation\Version::class] ?? null)
                    ->withGeneratedValueAnnotation($propertyAnnotations[Annotation\GeneratedValue::class] ?? null)
                    ->withSequenceGeneratorAnnotation($propertyAnnotations[Annotation\SequenceGenerator::class] ?? null)
                    ->withCustomIdGeneratorAnnotation($propertyAnnotations[Annotation\CustomIdGenerator::class] ?? null)
                    ->build();

                // Prevent column duplication
                $columnName = $fieldMetadata->getColumnName();

                if ($metadata->checkPropertyDuplication($columnName)) {
                    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                }

                $metadata->fieldNames[$fieldMetadata->getColumnName()] = $fieldMetadata->getName();

                return $fieldMetadata;
            case isset($propertyAnnotations[Annotation\OneToOne::class]):
                $oneToOneAssociationBuilder = new Builder\OneToOneAssociationMetadataBuilder($metadataBuildingContext);
                $associationMetadata        = $oneToOneAssociationBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->withOneToOneAnnotation($propertyAnnotations[Annotation\OneToOne::class] ?? null)
                    ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                    ->withCacheAnnotation($propertyAnnotations[Annotation\Cache::class] ?? null)
                    ->withJoinColumnsAnnotation($propertyAnnotations[Annotation\JoinColumns::class] ?? null)
                    ->withJoinColumnAnnotation($propertyAnnotations[Annotation\JoinColumn::class] ?? null)
                    ->build();

                // Prevent column duplication
                foreach ($associationMetadata->getJoinColumns() as $joinColumnMetadata) {
                    $columnName = $joinColumnMetadata->getColumnName();

                    // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                    //if ($metadata->checkPropertyDuplication($columnName)) {
                    //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                    //}

                    if ($associationMetadata->isOwningSide()) {
                        $metadata->fieldNames[$columnName] = $associationMetadata->getName();
                    }
                }

                return $associationMetadata;
            case isset($propertyAnnotations[Annotation\ManyToOne::class]):
                $manyToOneAssociationBuilder = new Builder\ManyToOneAssociationMetadataBuilder($metadataBuildingContext);
                $associationMetadata         = $manyToOneAssociationBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->withManyToOneAnnotation($propertyAnnotations[Annotation\ManyToOne::class] ?? null)
                    ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                    ->withCacheAnnotation($propertyAnnotations[Annotation\Cache::class] ?? null)
                    ->withJoinColumnsAnnotation($propertyAnnotations[Annotation\JoinColumns::class] ?? null)
                    ->withJoinColumnAnnotation($propertyAnnotations[Annotation\JoinColumn::class] ?? null)
                    ->build();

                // Prevent column duplication
                foreach ($associationMetadata->getJoinColumns() as $joinColumnMetadata) {
                    $columnName = $joinColumnMetadata->getColumnName();

                    // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                    //if ($metadata->checkPropertyDuplication($columnName)) {
                    //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                    //}

                    if ($associationMetadata->isOwningSide()) {
                        $metadata->fieldNames[$columnName] = $associationMetadata->getName();
                    }
                }

                return $associationMetadata;
            case isset($propertyAnnotations[Annotation\OneToMany::class]):
                $oneToManyAssociationBuilder = new Builder\OneToManyAssociationMetadataBuilder($metadataBuildingContext);

                return $oneToManyAssociationBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->withOneToManyAnnotation($propertyAnnotations[Annotation\OneToMany::class] ?? null)
                    ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                    ->withCacheAnnotation($propertyAnnotations[Annotation\Cache::class] ?? null)
                    ->withOrderByAnnotation($propertyAnnotations[Annotation\OrderBy::class] ?? null)
                    ->build();
            case isset($propertyAnnotations[Annotation\ManyToMany::class]):
                $manyToManyAssociationBuilder = new Builder\ManyToManyAssociationMetadataBuilder($metadataBuildingContext);

                return $manyToManyAssociationBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->withManyToManyAnnotation($propertyAnnotations[Annotation\ManyToMany::class] ?? null)
                    ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                    ->withCacheAnnotation($propertyAnnotations[Annotation\Cache::class] ?? null)
                    ->withJoinTableAnnotation($propertyAnnotations[Annotation\JoinTable::class] ?? null)
                    ->withOrderByAnnotation($propertyAnnotations[Annotation\OrderBy::class] ?? null)
                    ->build();
            case isset($propertyAnnotations[Annotation\Embedded::class]):
                return null;
            default:
                $transientBuilder = new Builder\TransientMetadataBuilder($metadataBuildingContext);

                return $transientBuilder
                    ->withComponentMetadata($metadata)
                    ->withFieldName($reflectionProperty->getName())
                    ->build();
        }
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     */
    private function attachLifecycleCallbacks(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata
    ) : void {
        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAnnotations[Annotation\HasLifecycleCallbacks::class])) {
            $eventMap = [
                Events::prePersist  => Annotation\PrePersist::class,
                Events::postPersist => Annotation\PostPersist::class,
                Events::preUpdate   => Annotation\PreUpdate::class,
                Events::postUpdate  => Annotation\PostUpdate::class,
                Events::preRemove   => Annotation\PreRemove::class,
                Events::postRemove  => Annotation\PostRemove::class,
                Events::postLoad    => Annotation\PostLoad::class,
                Events::preFlush    => Annotation\PreFlush::class,
            ];

            /** @var ReflectionMethod $reflectionMethod */
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                $annotations = $this->getMethodAnnotations($reflectionMethod);

                foreach ($eventMap as $eventName => $annotationClassName) {
                    if (isset($annotations[$annotationClassName])) {
                        $metadata->addLifecycleCallback($eventName, $reflectionMethod->getName());
                    }
                }
            }
        }
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @throws ReflectionException
     * @throws Mapping\MappingException
     */
    private function attachEntityListeners(
        array $classAnnotations,
        Mapping\ClassMetadata $metadata
    ) : void {
        // Evaluate @EntityListeners annotation
        if (isset($classAnnotations[Annotation\EntityListeners::class])) {
            /** @var Annotation\EntityListeners $entityListenersAnnot */
            $entityListenersAnnot = $classAnnotations[Annotation\EntityListeners::class];
            $eventMap             = [
                Events::prePersist  => Annotation\PrePersist::class,
                Events::postPersist => Annotation\PostPersist::class,
                Events::preUpdate   => Annotation\PreUpdate::class,
                Events::postUpdate  => Annotation\PostUpdate::class,
                Events::preRemove   => Annotation\PreRemove::class,
                Events::postRemove  => Annotation\PostRemove::class,
                Events::postLoad    => Annotation\PostLoad::class,
                Events::preFlush    => Annotation\PreFlush::class,
            ];

            foreach ($entityListenersAnnot->value as $listenerClassName) {
                if (! class_exists($listenerClassName)) {
                    throw Mapping\MappingException::entityListenerClassNotFound(
                        $listenerClassName,
                        $metadata->getClassName()
                    );
                }

                $listenerClass = new ReflectionClass($listenerClassName);

                /** @var ReflectionMethod $reflectionMethod */
                foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                    $annotations = $this->getMethodAnnotations($reflectionMethod);

                    foreach ($eventMap as $eventName => $annotationClassName) {
                        if (isset($annotations[$annotationClassName])) {
                            $metadata->addEntityListener($eventName, $listenerClassName, $reflectionMethod->getName());
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Annotation\Annotation[] $classAnnotations
     *
     * @throws Mapping\MappingException
     */
    private function attachPropertyOverrides(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : void {
        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations[Annotation\AssociationOverrides::class])) {
            $associationOverridesAnnot = $classAnnotations[Annotation\AssociationOverrides::class];

            foreach ($associationOverridesAnnot->value as $associationOverrideAnnotation) {
                $fieldName = $associationOverrideAnnotation->name;
                $property  = $metadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($metadata->getClassName(), $fieldName);
                }

                $override = clone $property;

                // Check for JoinColumn/JoinColumns annotations
                if ($associationOverrideAnnotation->joinColumns) {
                    $joinColumnBuilder = new Builder\JoinColumnMetadataBuilder($metadataBuildingContext);

                    $joinColumnBuilder
                        ->withComponentMetadata($metadata)
                        ->withFieldName($fieldName);

                    $joinColumns = [];

                    foreach ($associationOverrideAnnotation->joinColumns as $joinColumnAnnotation) {
                        $joinColumnBuilder->withJoinColumnAnnotation($joinColumnAnnotation);

                        $joinColumnMetadata = $joinColumnBuilder->build();
                        $columnName         = $joinColumnMetadata->getColumnName();

                        // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                        //if ($metadata->checkPropertyDuplication($columnName)) {
                        //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                        //}

                        if ($override->isOwningSide()) {
                            $metadata->fieldNames[$columnName] = $fieldName;
                        }

                        $joinColumns[] = $joinColumnMetadata;
                    }

                    $override->setJoinColumns($joinColumns);
                }

                // Check for JoinTable annotations
                if ($associationOverrideAnnotation->joinTable) {
                    $joinTableBuilder = new Builder\JoinTableMetadataBuilder($metadataBuildingContext);

                    $joinTableBuilder
                        ->withComponentMetadata($metadata)
                        ->withFieldName($fieldName)
                        ->withTargetEntity($property->getTargetEntity())
                        ->withJoinTableAnnotation($associationOverrideAnnotation->joinTable);

                    $override->setJoinTable($joinTableBuilder->build());
                }

                // Check for inversedBy
                if ($associationOverrideAnnotation->inversedBy) {
                    $override->setInversedBy($associationOverrideAnnotation->inversedBy);
                }

                // Check for fetch
                if ($associationOverrideAnnotation->fetch) {
                    $override->setFetchMode(constant(Mapping\FetchMode::class . '::' . $associationOverrideAnnotation->fetch));
                }

                $metadata->setPropertyOverride($override);
            }
        }

        // Evaluate AttributeOverrides annotation
        if (isset($classAnnotations[Annotation\AttributeOverrides::class])) {
            $attributeOverridesAnnot = $classAnnotations[Annotation\AttributeOverrides::class];
            $fieldBuilder            = new Builder\FieldMetadataBuilder($metadataBuildingContext);

            $fieldBuilder
                ->withComponentMetadata($metadata)
                ->withIdAnnotation(null)
                ->withVersionAnnotation(null);

            foreach ($attributeOverridesAnnot->value as $attributeOverrideAnnotation) {
                $fieldName = $attributeOverrideAnnotation->name;
                $property  = $metadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($metadata->getClassName(), $fieldName);
                }

                $fieldBuilder
                    ->withFieldName($fieldName)
                    ->withColumnAnnotation($attributeOverrideAnnotation->column);

                $fieldMetadata = $fieldBuilder->build();
                $columnName    = $fieldMetadata->getColumnName();

                // Prevent column duplication
                if ($metadata->checkPropertyDuplication($columnName)) {
                    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                }

                $metadata->fieldNames[$fieldMetadata->getColumnName()] = $fieldName;

                $metadata->setPropertyOverride($fieldMetadata);
            }
        }
    }

    /**
     * Attempts to resolve the cascade modes.
     *
     * @param string   $className        The class name.
     * @param string   $fieldName        The field name.
     * @param string[] $originalCascades The original unprocessed field cascades.
     *
     * @return string[] The processed field cascades.
     *
     * @throws Mapping\MappingException If a cascade option is not valid.
     */
    private function getCascade(string $className, string $fieldName, array $originalCascades) : array
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
     * Attempts to resolve the fetch mode.
     *
     * @param string $className The class name.
     * @param string $fetchMode The fetch mode.
     *
     * @return string The fetch mode as defined in ClassMetadata.
     *
     * @throws Mapping\MappingException If the fetch mode is not valid.
     */
    private function getFetchMode($className, $fetchMode) : string
    {
        $fetchModeConstant = sprintf('%s::%s', Mapping\FetchMode::class, $fetchMode);

        if (! defined($fetchModeConstant)) {
            throw Mapping\MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant($fetchModeConstant);
    }

    /**
     * @return Annotation\Annotation[]
     */
    private function getClassAnnotations(ReflectionClass $reflectionClass) : array
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
    private function getPropertyAnnotations(ReflectionProperty $reflectionProperty) : array
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
    private function getMethodAnnotations(ReflectionMethod $reflectionMethod) : array
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
