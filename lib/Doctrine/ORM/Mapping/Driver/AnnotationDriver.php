<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use UnexpectedValueException;

use function assert;
use function class_exists;
use function constant;
use function count;
use function defined;
use function get_class;
use function is_array;
use function is_numeric;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 */
class AnnotationDriver extends CompatibilityAnnotationDriver
{
    use ColocatedMappingDriver;

    /**
     * The annotation reader.
     *
     * @internal this property will be private in 3.0
     *
     * @var Reader
     */
    protected $reader;

    /**
     * @var int[]
     * @psalm-var array<class-string, int>
     */
    protected $entityAnnotationClasses = [
        Mapping\Entity::class => 1,
        Mapping\MappedSuperclass::class => 2,
    ];

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param Reader               $reader The AnnotationReader to use
     * @param string|string[]|null $paths  One or multiple paths where mapping classes can be found.
     */
    public function __construct($reader, $paths = null)
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/10098',
            'The annotation mapping driver is deprecated and will be removed in Doctrine ORM 3.0, please migrate to the attribute or XML driver.'
        );
        $this->reader = $reader;

        $this->addPaths((array) $paths);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param class-string<T> $className
     * @psalm-param ClassMetadata<T> $metadata
     *
     * @template T of object
     */
    public function loadMetadataForClass($className, PersistenceClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass()
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            ?? new ReflectionClass($metadata->name);

        $classAnnotations = $this->reader->getClassAnnotations($class);
        foreach ($classAnnotations as $key => $annot) {
            if (! is_numeric($key)) {
                continue;
            }

            $classAnnotations[get_class($annot)] = $annot;
        }

        // Evaluate Entity annotation
        if (isset($classAnnotations[Mapping\Entity::class])) {
            $entityAnnot = $classAnnotations[Mapping\Entity::class];
            assert($entityAnnot instanceof Mapping\Entity);
            if ($entityAnnot->repositoryClass !== null) {
                $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);
            }

            if ($entityAnnot->readOnly) {
                $metadata->markReadOnly();
            }
        } elseif (isset($classAnnotations[Mapping\MappedSuperclass::class])) {
            $mappedSuperclassAnnot = $classAnnotations[Mapping\MappedSuperclass::class];
            assert($mappedSuperclassAnnot instanceof Mapping\MappedSuperclass);

            $metadata->setCustomRepositoryClass($mappedSuperclassAnnot->repositoryClass);
            $metadata->isMappedSuperclass = true;
        } elseif (isset($classAnnotations[Mapping\Embeddable::class])) {
            $metadata->isEmbeddedClass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate Table annotation
        if (isset($classAnnotations[Mapping\Table::class])) {
            $tableAnnot = $classAnnotations[Mapping\Table::class];
            assert($tableAnnot instanceof Mapping\Table);
            $primaryTable = [
                'name'   => $tableAnnot->name,
                'schema' => $tableAnnot->schema,
            ];

            foreach ($tableAnnot->indexes ?? [] as $indexAnnot) {
                $index = [];

                if (! empty($indexAnnot->columns)) {
                    $index['columns'] = $indexAnnot->columns;
                }

                if (! empty($indexAnnot->fields)) {
                    $index['fields'] = $indexAnnot->fields;
                }

                if (
                    isset($index['columns'], $index['fields'])
                    || (
                        ! isset($index['columns'])
                        && ! isset($index['fields'])
                    )
                ) {
                    throw MappingException::invalidIndexConfiguration(
                        $className,
                        (string) ($indexAnnot->name ?? count($primaryTable['indexes']))
                    );
                }

                if (! empty($indexAnnot->flags)) {
                    $index['flags'] = $indexAnnot->flags;
                }

                if (! empty($indexAnnot->options)) {
                    $index['options'] = $indexAnnot->options;
                }

                if (! empty($indexAnnot->name)) {
                    $primaryTable['indexes'][$indexAnnot->name] = $index;
                } else {
                    $primaryTable['indexes'][] = $index;
                }
            }

            foreach ($tableAnnot->uniqueConstraints ?? [] as $uniqueConstraintAnnot) {
                $uniqueConstraint = [];

                if (! empty($uniqueConstraintAnnot->columns)) {
                    $uniqueConstraint['columns'] = $uniqueConstraintAnnot->columns;
                }

                if (! empty($uniqueConstraintAnnot->fields)) {
                    $uniqueConstraint['fields'] = $uniqueConstraintAnnot->fields;
                }

                if (
                    isset($uniqueConstraint['columns'], $uniqueConstraint['fields'])
                    || (
                        ! isset($uniqueConstraint['columns'])
                        && ! isset($uniqueConstraint['fields'])
                    )
                ) {
                    throw MappingException::invalidUniqueConstraintConfiguration(
                        $className,
                        (string) ($uniqueConstraintAnnot->name ?? count($primaryTable['uniqueConstraints']))
                    );
                }

                if (! empty($uniqueConstraintAnnot->options)) {
                    $uniqueConstraint['options'] = $uniqueConstraintAnnot->options;
                }

                if (! empty($uniqueConstraintAnnot->name)) {
                    $primaryTable['uniqueConstraints'][$uniqueConstraintAnnot->name] = $uniqueConstraint;
                } else {
                    $primaryTable['uniqueConstraints'][] = $uniqueConstraint;
                }
            }

            if ($tableAnnot->options) {
                $primaryTable['options'] = $tableAnnot->options;
            }

            $metadata->setPrimaryTable($primaryTable);
        }

        // Evaluate @Cache annotation
        if (isset($classAnnotations[Mapping\Cache::class])) {
            $cacheAnnot = $classAnnotations[Mapping\Cache::class];
            $cacheMap   = [
                'region' => $cacheAnnot->region,
                'usage'  => (int) constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAnnot->usage),
            ];

            $metadata->enableCache($cacheMap);
        }

        // Evaluate NamedNativeQueries annotation
        if (isset($classAnnotations[Mapping\NamedNativeQueries::class])) {
            $namedNativeQueriesAnnot = $classAnnotations[Mapping\NamedNativeQueries::class];

            foreach ($namedNativeQueriesAnnot->value as $namedNativeQuery) {
                $metadata->addNamedNativeQuery(
                    [
                        'name'              => $namedNativeQuery->name,
                        'query'             => $namedNativeQuery->query,
                        'resultClass'       => $namedNativeQuery->resultClass,
                        'resultSetMapping'  => $namedNativeQuery->resultSetMapping,
                    ]
                );
            }
        }

        // Evaluate SqlResultSetMappings annotation
        if (isset($classAnnotations[Mapping\SqlResultSetMappings::class])) {
            $sqlResultSetMappingsAnnot = $classAnnotations[Mapping\SqlResultSetMappings::class];

            foreach ($sqlResultSetMappingsAnnot->value as $resultSetMapping) {
                $entities = [];
                $columns  = [];
                foreach ($resultSetMapping->entities as $entityResultAnnot) {
                    $entityResult = [
                        'fields'                => [],
                        'entityClass'           => $entityResultAnnot->entityClass,
                        'discriminatorColumn'   => $entityResultAnnot->discriminatorColumn,
                    ];

                    foreach ($entityResultAnnot->fields as $fieldResultAnnot) {
                        $entityResult['fields'][] = [
                            'name'      => $fieldResultAnnot->name,
                            'column'    => $fieldResultAnnot->column,
                        ];
                    }

                    $entities[] = $entityResult;
                }

                foreach ($resultSetMapping->columns as $columnResultAnnot) {
                    $columns[] = [
                        'name' => $columnResultAnnot->name,
                    ];
                }

                $metadata->addSqlResultSetMapping(
                    [
                        'name'          => $resultSetMapping->name,
                        'entities'      => $entities,
                        'columns'       => $columns,
                    ]
                );
            }
        }

        // Evaluate NamedQueries annotation
        if (isset($classAnnotations[Mapping\NamedQueries::class])) {
            $namedQueriesAnnot = $classAnnotations[Mapping\NamedQueries::class];

            if (! is_array($namedQueriesAnnot->value)) {
                throw new UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
            }

            foreach ($namedQueriesAnnot->value as $namedQuery) {
                if (! ($namedQuery instanceof Mapping\NamedQuery)) {
                    throw new UnexpectedValueException('@NamedQueries should contain an array of @NamedQuery annotations.');
                }

                $metadata->addNamedQuery(
                    [
                        'name'  => $namedQuery->name,
                        'query' => $namedQuery->query,
                    ]
                );
            }
        }

        // Evaluate InheritanceType annotation
        if (isset($classAnnotations[Mapping\InheritanceType::class])) {
            $inheritanceTypeAnnot = $classAnnotations[Mapping\InheritanceType::class];
            assert($inheritanceTypeAnnot instanceof Mapping\InheritanceType);

            $metadata->setInheritanceType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value)
            );

            if ($metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
                // Evaluate DiscriminatorColumn annotation
                if (isset($classAnnotations[Mapping\DiscriminatorColumn::class])) {
                    $discrColumnAnnot = $classAnnotations[Mapping\DiscriminatorColumn::class];
                    assert($discrColumnAnnot instanceof Mapping\DiscriminatorColumn);

                    $columnDef = [
                        'name' => $discrColumnAnnot->name,
                        'type' => $discrColumnAnnot->type ?: 'string',
                        'length' => $discrColumnAnnot->length ?? 255,
                        'columnDefinition' => $discrColumnAnnot->columnDefinition,
                        'enumType' => $discrColumnAnnot->enumType,
                    ];

                    if ($discrColumnAnnot->options) {
                        $columnDef['options'] = $discrColumnAnnot->options;
                    }

                    $metadata->setDiscriminatorColumn($columnDef);
                } else {
                    $metadata->setDiscriminatorColumn(['name' => 'dtype', 'type' => 'string', 'length' => 255]);
                }

                // Evaluate DiscriminatorMap annotation
                if (isset($classAnnotations[Mapping\DiscriminatorMap::class])) {
                    $discrMapAnnot = $classAnnotations[Mapping\DiscriminatorMap::class];
                    assert($discrMapAnnot instanceof Mapping\DiscriminatorMap);
                    $metadata->setDiscriminatorMap($discrMapAnnot->value);
                }
            }
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations[Mapping\ChangeTrackingPolicy::class])) {
            $changeTrackingAnnot = $classAnnotations[Mapping\ChangeTrackingPolicy::class];
            assert($changeTrackingAnnot instanceof Mapping\ChangeTrackingPolicy);
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAnnot->value));
        }

        // Evaluate annotations on properties/fields
        foreach ($class->getProperties() as $property) {
            if (
                $metadata->isMappedSuperclass && ! $property->isPrivate()
                ||
                $metadata->isInheritedField($property->name)
                ||
                $metadata->isInheritedAssociation($property->name)
                ||
                $metadata->isInheritedEmbeddedClass($property->name)
            ) {
                continue;
            }

            $mapping              = [];
            $mapping['fieldName'] = $property->getName();

            // Evaluate @Cache annotation
            $cacheAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Cache::class);
            if ($cacheAnnot !== null) {
                $mapping['cache'] = $metadata->getAssociationCacheDefaults(
                    $mapping['fieldName'],
                    [
                        'usage'  => (int) constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAnnot->usage),
                        'region' => $cacheAnnot->region,
                    ]
                );
            }

            // Check for JoinColumn/JoinColumns annotations
            $joinColumns = [];

            $joinColumnAnnot = $this->reader->getPropertyAnnotation($property, Mapping\JoinColumn::class);
            if ($joinColumnAnnot) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAnnot);
            } else {
                $joinColumnsAnnot = $this->reader->getPropertyAnnotation($property, Mapping\JoinColumns::class);
                if ($joinColumnsAnnot) {
                    foreach ($joinColumnsAnnot->value as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }
                }
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            $columnAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Column::class);
            if ($columnAnnot) {
                $mapping = $this->columnToArray($property->getName(), $columnAnnot);

                $idAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Id::class);
                if ($idAnnot) {
                    $mapping['id'] = true;
                }

                $generatedValueAnnot = $this->reader->getPropertyAnnotation($property, Mapping\GeneratedValue::class);
                if ($generatedValueAnnot) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
                }

                if ($this->reader->getPropertyAnnotation($property, Mapping\Version::class)) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                $seqGeneratorAnnot = $this->reader->getPropertyAnnotation($property, Mapping\SequenceGenerator::class);
                if ($seqGeneratorAnnot) {
                    $metadata->setSequenceGeneratorDefinition(
                        [
                            'sequenceName' => $seqGeneratorAnnot->sequenceName,
                            'allocationSize' => $seqGeneratorAnnot->allocationSize,
                            'initialValue' => $seqGeneratorAnnot->initialValue,
                        ]
                    );
                } else {
                    $customGeneratorAnnot = $this->reader->getPropertyAnnotation($property, Mapping\CustomIdGenerator::class);
                    if ($customGeneratorAnnot) {
                        $metadata->setCustomGeneratorDefinition(
                            [
                                'class' => $customGeneratorAnnot->class,
                            ]
                        );
                    }
                }
            } else {
                $this->loadRelationShipMapping(
                    $property,
                    $mapping,
                    $metadata,
                    $joinColumns,
                    $className
                );
            }
        }

        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations[Mapping\AssociationOverrides::class])) {
            $associationOverridesAnnot = $classAnnotations[Mapping\AssociationOverrides::class];
            assert($associationOverridesAnnot instanceof Mapping\AssociationOverrides);

            foreach ($associationOverridesAnnot->overrides as $associationOverride) {
                $override  = [];
                $fieldName = $associationOverride->name;

                // Check for JoinColumn/JoinColumns annotations
                if ($associationOverride->joinColumns) {
                    $joinColumns = [];

                    foreach ($associationOverride->joinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['joinColumns'] = $joinColumns;
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTableAnnot = $associationOverride->joinTable;
                    $joinTable      = [
                        'name'      => $joinTableAnnot->name,
                        'schema'    => $joinTableAnnot->schema,
                    ];

                    foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['joinTable'] = $joinTable;
                }

                // Check for inversedBy
                if ($associationOverride->inversedBy) {
                    $override['inversedBy'] = $associationOverride->inversedBy;
                }

                // Check for `fetch`
                if ($associationOverride->fetch) {
                    $override['fetch'] = constant(Mapping\ClassMetadata::class . '::FETCH_' . $associationOverride->fetch);
                }

                $metadata->setAssociationOverride($fieldName, $override);
            }
        }

        // Evaluate AttributeOverrides annotation
        if (isset($classAnnotations[Mapping\AttributeOverrides::class])) {
            $attributeOverridesAnnot = $classAnnotations[Mapping\AttributeOverrides::class];
            assert($attributeOverridesAnnot instanceof Mapping\AttributeOverrides);

            foreach ($attributeOverridesAnnot->overrides as $attributeOverrideAnnot) {
                $attributeOverride = $this->columnToArray($attributeOverrideAnnot->name, $attributeOverrideAnnot->column);

                $metadata->setAttributeOverride($attributeOverrideAnnot->name, $attributeOverride);
            }
        }

        // Evaluate EntityListeners annotation
        if (isset($classAnnotations[Mapping\EntityListeners::class])) {
            $entityListenersAnnot = $classAnnotations[Mapping\EntityListeners::class];
            assert($entityListenersAnnot instanceof Mapping\EntityListeners);

            foreach ($entityListenersAnnot->value as $item) {
                $listenerClassName = $metadata->fullyQualifiedClassName($item);

                if (! class_exists($listenerClassName)) {
                    throw MappingException::entityListenerClassNotFound($listenerClassName, $className);
                }

                $hasMapping    = false;
                $listenerClass = new ReflectionClass($listenerClassName);

                foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    // find method callbacks.
                    $callbacks  = $this->getMethodCallbacks($method);
                    $hasMapping = $hasMapping ?: ! empty($callbacks);

                    foreach ($callbacks as $value) {
                        $metadata->addEntityListener($value[1], $listenerClassName, $value[0]);
                    }
                }

                // Evaluate the listener using naming convention.
                if (! $hasMapping) {
                    EntityListenerBuilder::bindEntityListener($metadata, $listenerClassName);
                }
            }
        }

        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAnnotations[Mapping\HasLifecycleCallbacks::class])) {
            foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($this->getMethodCallbacks($method) as $value) {
                    $metadata->addLifecycleCallback($value[0], $value[1]);
                }
            }
        }
    }

    /**
     * @param mixed[]              $joinColumns
     * @param class-string         $className
     * @param array<string, mixed> $mapping
     */
    private function loadRelationShipMapping(
        ReflectionProperty $property,
        array &$mapping,
        PersistenceClassMetadata $metadata,
        array $joinColumns,
        string $className
    ): void {
        $oneToOneAnnot = $this->reader->getPropertyAnnotation($property, Mapping\OneToOne::class);
        if ($oneToOneAnnot) {
            $idAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Id::class);
            if ($idAnnot) {
                $mapping['id'] = true;
            }

            $mapping['targetEntity']  = $oneToOneAnnot->targetEntity;
            $mapping['joinColumns']   = $joinColumns;
            $mapping['mappedBy']      = $oneToOneAnnot->mappedBy;
            $mapping['inversedBy']    = $oneToOneAnnot->inversedBy;
            $mapping['cascade']       = $oneToOneAnnot->cascade;
            $mapping['orphanRemoval'] = $oneToOneAnnot->orphanRemoval;
            $mapping['fetch']         = $this->getFetchMode($className, $oneToOneAnnot->fetch);
            $metadata->mapOneToOne($mapping);

            return;
        }

        $oneToManyAnnot = $this->reader->getPropertyAnnotation($property, Mapping\OneToMany::class);
        if ($oneToManyAnnot) {
            $mapping['mappedBy']      = $oneToManyAnnot->mappedBy;
            $mapping['targetEntity']  = $oneToManyAnnot->targetEntity;
            $mapping['cascade']       = $oneToManyAnnot->cascade;
            $mapping['indexBy']       = $oneToManyAnnot->indexBy;
            $mapping['orphanRemoval'] = $oneToManyAnnot->orphanRemoval;
            $mapping['fetch']         = $this->getFetchMode($className, $oneToManyAnnot->fetch);

            $orderByAnnot = $this->reader->getPropertyAnnotation($property, Mapping\OrderBy::class);
            if ($orderByAnnot) {
                $mapping['orderBy'] = $orderByAnnot->value;
            }

            $metadata->mapOneToMany($mapping);
        }

        $manyToOneAnnot = $this->reader->getPropertyAnnotation($property, Mapping\ManyToOne::class);
        if ($manyToOneAnnot) {
            $idAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Id::class);
            if ($idAnnot) {
                $mapping['id'] = true;
            }

            $mapping['joinColumns']  = $joinColumns;
            $mapping['cascade']      = $manyToOneAnnot->cascade;
            $mapping['inversedBy']   = $manyToOneAnnot->inversedBy;
            $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
            $mapping['fetch']        = $this->getFetchMode($className, $manyToOneAnnot->fetch);
            $metadata->mapManyToOne($mapping);
        }

        $manyToManyAnnot = $this->reader->getPropertyAnnotation($property, Mapping\ManyToMany::class);
        if ($manyToManyAnnot) {
            $joinTable = [];

            $joinTableAnnot = $this->reader->getPropertyAnnotation($property, Mapping\JoinTable::class);
            if ($joinTableAnnot) {
                $joinTable = [
                    'name' => $joinTableAnnot->name,
                    'schema' => $joinTableAnnot->schema,
                ];

                if ($joinTableAnnot->options) {
                    $joinTable['options'] = $joinTableAnnot->options;
                }

                foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                    $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                }

                foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                    $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                }
            }

            $mapping['joinTable']     = $joinTable;
            $mapping['targetEntity']  = $manyToManyAnnot->targetEntity;
            $mapping['mappedBy']      = $manyToManyAnnot->mappedBy;
            $mapping['inversedBy']    = $manyToManyAnnot->inversedBy;
            $mapping['cascade']       = $manyToManyAnnot->cascade;
            $mapping['indexBy']       = $manyToManyAnnot->indexBy;
            $mapping['orphanRemoval'] = $manyToManyAnnot->orphanRemoval;
            $mapping['fetch']         = $this->getFetchMode($className, $manyToManyAnnot->fetch);

            $orderByAnnot = $this->reader->getPropertyAnnotation($property, Mapping\OrderBy::class);
            if ($orderByAnnot) {
                $mapping['orderBy'] = $orderByAnnot->value;
            }

            $metadata->mapManyToMany($mapping);
        }

        $embeddedAnnot = $this->reader->getPropertyAnnotation($property, Mapping\Embedded::class);
        if ($embeddedAnnot) {
            $mapping['class']        = $embeddedAnnot->class;
            $mapping['columnPrefix'] = $embeddedAnnot->columnPrefix;

            $metadata->mapEmbedded($mapping);
        }
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param class-string $className
     *
     * @psalm-return ClassMetadata::FETCH_* The fetch mode as defined in ClassMetadata.
     *
     * @throws MappingException If the fetch mode is not valid.
     */
    private function getFetchMode(string $className, string $fetchMode): int
    {
        if (! defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
            throw MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode);
    }

    /**
     * Attempts to resolve the generated mode.
     *
     * @psalm-return ClassMetadata::GENERATED_*
     *
     * @throws MappingException If the fetch mode is not valid.
     */
    private function getGeneratedMode(string $generatedMode): int
    {
        if (! defined('Doctrine\ORM\Mapping\ClassMetadata::GENERATED_' . $generatedMode)) {
            throw MappingException::invalidGeneratedMode($generatedMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATED_' . $generatedMode);
    }

    /**
     * Parses the given method.
     *
     * @return list<array{string, string}>
     * @psalm-return list<array{string, (Events::*)}>
     */
    private function getMethodCallbacks(ReflectionMethod $method): array
    {
        $callbacks   = [];
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annot) {
            if ($annot instanceof Mapping\PrePersist) {
                $callbacks[] = [$method->name, Events::prePersist];
            }

            if ($annot instanceof Mapping\PostPersist) {
                $callbacks[] = [$method->name, Events::postPersist];
            }

            if ($annot instanceof Mapping\PreUpdate) {
                $callbacks[] = [$method->name, Events::preUpdate];
            }

            if ($annot instanceof Mapping\PostUpdate) {
                $callbacks[] = [$method->name, Events::postUpdate];
            }

            if ($annot instanceof Mapping\PreRemove) {
                $callbacks[] = [$method->name, Events::preRemove];
            }

            if ($annot instanceof Mapping\PostRemove) {
                $callbacks[] = [$method->name, Events::postRemove];
            }

            if ($annot instanceof Mapping\PostLoad) {
                $callbacks[] = [$method->name, Events::postLoad];
            }

            if ($annot instanceof Mapping\PreFlush) {
                $callbacks[] = [$method->name, Events::preFlush];
            }
        }

        return $callbacks;
    }

    /**
     * Parse the given JoinColumn as array
     *
     * @return mixed[]
     * @psalm-return array{
     *                   name: string|null,
     *                   unique: bool,
     *                   nullable: bool,
     *                   onDelete: mixed,
     *                   columnDefinition: string|null,
     *                   referencedColumnName: string,
     *                   options?: array<string, mixed>
     *               }
     */
    private function joinColumnToArray(Mapping\JoinColumn $joinColumn): array
    {
        $mapping = [
            'name' => $joinColumn->name,
            'unique' => $joinColumn->unique,
            'nullable' => $joinColumn->nullable,
            'onDelete' => $joinColumn->onDelete,
            'columnDefinition' => $joinColumn->columnDefinition,
            'referencedColumnName' => $joinColumn->referencedColumnName,
        ];

        if ($joinColumn->options) {
            $mapping['options'] = $joinColumn->options;
        }

        return $mapping;
    }

    /**
     * Parse the given Column as array
     *
     * @return mixed[]
     * @psalm-return array{
     *                   fieldName: string,
     *                   type: mixed,
     *                   scale: int,
     *                   length: int,
     *                   unique: bool,
     *                   nullable: bool,
     *                   precision: int,
     *                   notInsertable?: bool,
     *                   notUpdateble?: bool,
     *                   generated?: ClassMetadata::GENERATED_*,
     *                   enumType?: class-string,
     *                   options?: mixed[],
     *                   columnName?: string,
     *                   columnDefinition?: string
     *               }
     */
    private function columnToArray(string $fieldName, Mapping\Column $column): array
    {
        $mapping = [
            'fieldName'     => $fieldName,
            'type'          => $column->type,
            'scale'         => $column->scale,
            'length'        => $column->length,
            'unique'        => $column->unique,
            'nullable'      => $column->nullable,
            'precision'     => $column->precision,
        ];

        if (! $column->insertable) {
            $mapping['notInsertable'] = true;
        }

        if (! $column->updatable) {
            $mapping['notUpdatable'] = true;
        }

        if ($column->generated) {
            $mapping['generated'] = $this->getGeneratedMode($column->generated);
        }

        if ($column->options) {
            $mapping['options'] = $column->options;
        }

        if (isset($column->name)) {
            $mapping['columnName'] = $column->name;
        }

        if (isset($column->columnDefinition)) {
            $mapping['columnDefinition'] = $column->columnDefinition;
        }

        if ($column->enumType !== null) {
            $mapping['enumType'] = $column->enumType;
        }

        return $mapping;
    }

    /**
     * Retrieve the current annotation reader
     *
     * @return Reader
     */
    public function getReader()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9587',
            '%s is deprecated with no replacement',
            __METHOD__
        );

        return $this->reader;
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className)
    {
        $classAnnotations = $this->reader->getClassAnnotations(new ReflectionClass($className));

        foreach ($classAnnotations as $annot) {
            if (isset($this->entityAnnotationClasses[get_class($annot)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Factory method for the Annotation Driver.
     *
     * @param mixed[]|string $paths
     *
     * @return AnnotationDriver
     */
    public static function create($paths = [], ?AnnotationReader $reader = null)
    {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}
