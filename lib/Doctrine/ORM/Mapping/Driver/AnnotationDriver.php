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
use function array_merge;
use function array_unique;
use function class_exists;
use function constant;
use function get_class;
use function get_declared_classes;
use function in_array;
use function is_dir;
use function is_numeric;
use function preg_match;
use function preg_quote;
use function realpath;
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
        $this->paths = \array_unique(\array_merge($this->paths, $paths));
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
        $this->excludePaths = \array_unique(\array_merge($this->excludePaths, $paths));
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
            if (isset($this->entityAnnotationClasses[\get_class($annotation)])) {
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
            if (! \is_dir($path)) {
                throw Mapping\MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . \preg_quote($this->fileExtension) . '$/i',
                RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (! \preg_match('(^phar:)i', $sourceFile)) {
                    $sourceFile = \realpath($sourceFile);
                }

                foreach ($this->excludePaths as $excludePath) {
                    $exclude = \str_replace('\\', '/', \realpath($excludePath));
                    $current = \str_replace('\\', '/', $sourceFile);

                    if (\strpos($current, $exclude) !== false) {
                        continue 2;
                    }
                }

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = \get_declared_classes();

        foreach ($declared as $className) {
            $reflectionClass = new ReflectionClass($className);
            $sourceFile      = $reflectionClass->getFileName();

            if (\in_array($sourceFile, $includedFiles, true) && ! $this->isTransient($className)) {
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
        $classAnnotations = $this->getClassAnnotations($reflectionClass);
        $classBuilder     = new Builder\ClassMetadataBuilder($metadataBuildingContext);
        $classMetadata    = $classBuilder
            ->withClassName($reflectionClass->getName())
            ->withParentMetadata($parent)
            ->withEntityAnnotation($classAnnotations[Annotation\Entity::class] ?? null)
            ->withMappedSuperclassAnnotation($classAnnotations[Annotation\MappedSuperclass::class] ?? null)
            ->withEmbeddableAnnotation($classAnnotations[Annotation\Embeddable::class] ?? null)
            ->withTableAnnotation($classAnnotations[Annotation\Table::class] ?? null)
            ->withInheritanceTypeAnnotation($classAnnotations[Annotation\InheritanceType::class] ?? null)
            ->withDiscriminatorColumnAnnotation($classAnnotations[Annotation\DiscriminatorColumn::class] ?? null)
            ->withDiscriminatorMapAnnotation($classAnnotations[Annotation\DiscriminatorMap::class] ?? null)
            ->withChangeTrackingPolicyAnnotation($classAnnotations[Annotation\ChangeTrackingPolicy::class] ?? null)
            ->withCacheAnnotation($classAnnotations[Annotation\Cache::class] ?? null)
            ->build();

        if (! $classMetadata->isEmbeddedClass) {
            $this->attachLifecycleCallbacks($classAnnotations, $reflectionClass, $classMetadata);
            $this->attachEntityListeners($classAnnotations, $classMetadata);
        }

        $this->attachProperties($classAnnotations, $reflectionClass, $classMetadata, $metadataBuildingContext);
        $this->attachPropertyOverrides($classAnnotations, $reflectionClass, $classMetadata, $metadataBuildingContext);

        return $classMetadata;
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
                if (! \class_exists($listenerClassName)) {
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
    private function attachProperties(
        array $classAnnotations,
        ReflectionClass $reflectionClass,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : void {
        // Evaluate annotations on properties/fields
        $propertyBuilder = new Builder\PropertyMetadataBuilder($metadataBuildingContext);

        /** @var ReflectionProperty $reflProperty */
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
                continue;
            }

            $propertyAnnotations = $this->getPropertyAnnotations($reflectionProperty);
            $propertyMetadata    = $propertyBuilder
                ->withComponentMetadata($metadata)
                ->withFieldName($reflectionProperty->getName())
                ->withIdAnnotation($propertyAnnotations[Annotation\Id::class] ?? null)
                ->withCacheAnnotation($propertyAnnotations[Annotation\Cache::class] ?? null)
                ->withColumnAnnotation($propertyAnnotations[Annotation\Column::class] ?? null)
                ->withEmbeddedAnnotation($propertyAnnotations[Annotation\Embedded::class] ?? null)
                ->withOneToOneAnnotation($propertyAnnotations[Annotation\OneToOne::class] ?? null)
                ->withManyToOneAnnotation($propertyAnnotations[Annotation\ManyToOne::class] ?? null)
                ->withOneToManyAnnotation($propertyAnnotations[Annotation\OneToMany::class] ?? null)
                ->withManyToManyAnnotation($propertyAnnotations[Annotation\ManyToMany::class] ?? null)
                ->withJoinTableAnnotation($propertyAnnotations[Annotation\JoinTable::class] ?? null)
                ->withJoinColumnsAnnotation($propertyAnnotations[Annotation\JoinColumns::class] ?? null)
                ->withJoinColumnAnnotation($propertyAnnotations[Annotation\JoinColumn::class] ?? null)
                ->withOrderByAnnotation($propertyAnnotations[Annotation\OrderBy::class] ?? null)
                ->withVersionAnnotation($propertyAnnotations[Annotation\Version::class] ?? null)
                ->withGeneratedValueAnnotation($propertyAnnotations[Annotation\GeneratedValue::class] ?? null)
                ->withSequenceGeneratorAnnotation($propertyAnnotations[Annotation\SequenceGenerator::class] ?? null)
                ->withCustomIdGeneratorAnnotation($propertyAnnotations[Annotation\CustomIdGenerator::class] ?? null)
                ->build();

            $metadata->addProperty($propertyMetadata);
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
                    $override->setFetchMode(\constant(Mapping\FetchMode::class . '::' . $associationOverrideAnnotation->fetch));
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

                $metadata->setPropertyOverride($fieldMetadata);
            }
        }
    }

    /**
     * @return Annotation\Annotation[]
     */
    private function getClassAnnotations(ReflectionClass $reflectionClass) : array
    {
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        foreach ($classAnnotations as $key => $annot) {
            if (! \is_numeric($key)) {
                continue;
            }

            $classAnnotations[\get_class($annot)] = $annot;
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
            if (! \is_numeric($key)) {
                continue;
            }

            $propertyAnnotations[\get_class($annot)] = $annot;
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
            if (! \is_numeric($key)) {
                continue;
            }

            $methodAnnotations[\get_class($annot)] = $annot;
        }

        return $methodAnnotations;
    }
}
