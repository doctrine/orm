<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingAttribute;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\Driver\ColocatedMappingDriver;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function class_exists;
use function constant;
use function defined;
use function get_class;
use function sprintf;

use const PHP_VERSION_ID;

class AttributeDriver extends CompatibilityAnnotationDriver
{
    use ColocatedMappingDriver;

    private const ENTITY_ATTRIBUTE_CLASSES = [
        Mapping\Entity::class => 1,
        Mapping\MappedSuperclass::class => 2,
    ];

    /**
     * @deprecated override isTransient() instead of overriding this property
     *
     * @var array<class-string<MappingAttribute>, int>
     */
    protected $entityAnnotationClasses = self::ENTITY_ATTRIBUTE_CLASSES;

    /**
     * The attribute reader.
     *
     * @internal this property will be private in 3.0
     *
     * @var AttributeReader
     */
    protected $reader;

    /** @param array<string> $paths */
    public function __construct(array $paths)
    {
        if (PHP_VERSION_ID < 80000) {
            throw new LogicException(sprintf(
                'The attribute metadata driver cannot be enabled on PHP 7. Please upgrade to PHP 8 or choose a different'
                . ' metadata driver.'
            ));
        }

        $this->reader = new AttributeReader();
        $this->addPaths($paths);

        if ($this->entityAnnotationClasses !== self::ENTITY_ATTRIBUTE_CLASSES) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/10204',
                'Changing the value of %s::$entityAnnotationClasses is deprecated and will have no effect in Doctrine ORM 3.0.',
                self::class
            );
        }
    }

    /**
     * Retrieve the current annotation reader
     *
     * @deprecated no replacement planned.
     *
     * @return AttributeReader
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
        $classAttributes = $this->reader->getClassAttributes(new ReflectionClass($className));

        foreach ($classAttributes as $a) {
            $attr = $a instanceof RepeatableAttributeCollection ? $a[0] : $a;
            if (isset($this->entityAnnotationClasses[get_class($attr)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-param class-string<T> $className
     * @psalm-param ClassMetadata<T> $metadata
     *
     * @template T of object
     */
    public function loadMetadataForClass($className, PersistenceClassMetadata $metadata): void
    {
        $reflectionClass = $metadata->getReflectionClass()
            // this happens when running attribute driver in combination with
            // static reflection services. This is not the nicest fix
            ?? new ReflectionClass($metadata->name);

        $classAttributes = $this->reader->getClassAttributes($reflectionClass);

        // Evaluate Entity attribute
        if (isset($classAttributes[Mapping\Entity::class])) {
            $entityAttribute = $classAttributes[Mapping\Entity::class];
            if ($entityAttribute->repositoryClass !== null) {
                $metadata->setCustomRepositoryClass($entityAttribute->repositoryClass);
            }

            if ($entityAttribute->readOnly) {
                $metadata->markReadOnly();
            }
        } elseif (isset($classAttributes[Mapping\MappedSuperclass::class])) {
            $mappedSuperclassAttribute = $classAttributes[Mapping\MappedSuperclass::class];

            $metadata->setCustomRepositoryClass($mappedSuperclassAttribute->repositoryClass);
            $metadata->isMappedSuperclass = true;
        } elseif (isset($classAttributes[Mapping\Embeddable::class])) {
            $metadata->isEmbeddedClass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        $primaryTable = [];

        if (isset($classAttributes[Mapping\Table::class])) {
            $tableAnnot             = $classAttributes[Mapping\Table::class];
            $primaryTable['name']   = $tableAnnot->name;
            $primaryTable['schema'] = $tableAnnot->schema;

            if ($tableAnnot->options) {
                $primaryTable['options'] = $tableAnnot->options;
            }
        }

        if (isset($classAttributes[Mapping\Index::class])) {
            foreach ($classAttributes[Mapping\Index::class] as $idx => $indexAnnot) {
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
                        (string) ($indexAnnot->name ?? $idx)
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
        }

        if (isset($classAttributes[Mapping\UniqueConstraint::class])) {
            foreach ($classAttributes[Mapping\UniqueConstraint::class] as $idx => $uniqueConstraintAnnot) {
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
                        (string) ($uniqueConstraintAnnot->name ?? $idx)
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
        }

        $metadata->setPrimaryTable($primaryTable);

        // Evaluate #[Cache] attribute
        if (isset($classAttributes[Mapping\Cache::class])) {
            $cacheAttribute = $classAttributes[Mapping\Cache::class];
            $cacheMap       = [
                'region' => $cacheAttribute->region,
                'usage'  => constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAttribute->usage),
            ];

            $metadata->enableCache($cacheMap);
        }

        // Evaluate InheritanceType attribute
        if (isset($classAttributes[Mapping\InheritanceType::class])) {
            $inheritanceTypeAttribute = $classAttributes[Mapping\InheritanceType::class];

            $metadata->setInheritanceType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAttribute->value)
            );

            if ($metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
                // Evaluate DiscriminatorColumn attribute
                if (isset($classAttributes[Mapping\DiscriminatorColumn::class])) {
                    $discrColumnAttribute = $classAttributes[Mapping\DiscriminatorColumn::class];

                    $metadata->setDiscriminatorColumn(
                        [
                            'name'             => isset($discrColumnAttribute->name) ? (string) $discrColumnAttribute->name : null,
                            'type'             => isset($discrColumnAttribute->type) ? (string) $discrColumnAttribute->type : 'string',
                            'length'           => isset($discrColumnAttribute->length) ? (int) $discrColumnAttribute->length : 255,
                            'columnDefinition' => isset($discrColumnAttribute->columnDefinition) ? (string) $discrColumnAttribute->columnDefinition : null,
                            'enumType'         => isset($discrColumnAttribute->enumType) ? (string) $discrColumnAttribute->enumType : null,
                        ]
                    );
                } else {
                    $metadata->setDiscriminatorColumn(['name' => 'dtype', 'type' => 'string', 'length' => 255]);
                }

                // Evaluate DiscriminatorMap attribute
                if (isset($classAttributes[Mapping\DiscriminatorMap::class])) {
                    $discrMapAttribute = $classAttributes[Mapping\DiscriminatorMap::class];
                    $metadata->setDiscriminatorMap($discrMapAttribute->value);
                }
            }
        }

        // Evaluate DoctrineChangeTrackingPolicy attribute
        if (isset($classAttributes[Mapping\ChangeTrackingPolicy::class])) {
            $changeTrackingAttribute = $classAttributes[Mapping\ChangeTrackingPolicy::class];
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAttribute->value));
        }

        foreach ($reflectionClass->getProperties() as $property) {
            assert($property instanceof ReflectionProperty);
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

            // Evaluate #[Cache] attribute
            $cacheAttribute = $this->reader->getPropertyAttribute($property, Mapping\Cache::class);
            if ($cacheAttribute !== null) {
                assert($cacheAttribute instanceof Mapping\Cache);

                $mapping['cache'] = $metadata->getAssociationCacheDefaults(
                    $mapping['fieldName'],
                    [
                        'usage'  => (int) constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAttribute->usage),
                        'region' => $cacheAttribute->region,
                    ]
                );
            }

            // Check for JoinColumn/JoinColumns attributes
            $joinColumns = [];

            $joinColumnAttributes = $this->reader->getPropertyAttributeCollection($property, Mapping\JoinColumn::class);

            foreach ($joinColumnAttributes as $joinColumnAttribute) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAttribute);
            }

            // Field can only be attributed with one of:
            // Column, OneToOne, OneToMany, ManyToOne, ManyToMany, Embedded
            $columnAttribute     = $this->reader->getPropertyAttribute($property, Mapping\Column::class);
            $oneToOneAttribute   = $this->reader->getPropertyAttribute($property, Mapping\OneToOne::class);
            $oneToManyAttribute  = $this->reader->getPropertyAttribute($property, Mapping\OneToMany::class);
            $manyToOneAttribute  = $this->reader->getPropertyAttribute($property, Mapping\ManyToOne::class);
            $manyToManyAttribute = $this->reader->getPropertyAttribute($property, Mapping\ManyToMany::class);
            $embeddedAttribute   = $this->reader->getPropertyAttribute($property, Mapping\Embedded::class);

            if ($columnAttribute !== null) {
                $mapping = $this->columnToArray($property->getName(), $columnAttribute);

                if ($this->reader->getPropertyAttribute($property, Mapping\Id::class)) {
                    $mapping['id'] = true;
                }

                $generatedValueAttribute = $this->reader->getPropertyAttribute($property, Mapping\GeneratedValue::class);

                if ($generatedValueAttribute !== null) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAttribute->strategy));
                }

                if ($this->reader->getPropertyAttribute($property, Mapping\Version::class)) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                $seqGeneratorAttribute    = $this->reader->getPropertyAttribute($property, Mapping\SequenceGenerator::class);
                $customGeneratorAttribute = $this->reader->getPropertyAttribute($property, Mapping\CustomIdGenerator::class);

                if ($seqGeneratorAttribute !== null) {
                    $metadata->setSequenceGeneratorDefinition(
                        [
                            'sequenceName' => $seqGeneratorAttribute->sequenceName,
                            'allocationSize' => $seqGeneratorAttribute->allocationSize,
                            'initialValue' => $seqGeneratorAttribute->initialValue,
                        ]
                    );
                } elseif ($customGeneratorAttribute !== null) {
                    $metadata->setCustomGeneratorDefinition(
                        [
                            'class' => $customGeneratorAttribute->class,
                        ]
                    );
                }
            } elseif ($oneToOneAttribute !== null) {
                if ($this->reader->getPropertyAttribute($property, Mapping\Id::class)) {
                    $mapping['id'] = true;
                }

                $mapping['targetEntity']  = $oneToOneAttribute->targetEntity;
                $mapping['joinColumns']   = $joinColumns;
                $mapping['mappedBy']      = $oneToOneAttribute->mappedBy;
                $mapping['inversedBy']    = $oneToOneAttribute->inversedBy;
                $mapping['cascade']       = $oneToOneAttribute->cascade;
                $mapping['orphanRemoval'] = $oneToOneAttribute->orphanRemoval;
                $mapping['fetch']         = $this->getFetchMode($className, $oneToOneAttribute->fetch);
                $metadata->mapOneToOne($mapping);
            } elseif ($oneToManyAttribute !== null) {
                $mapping['mappedBy']      = $oneToManyAttribute->mappedBy;
                $mapping['targetEntity']  = $oneToManyAttribute->targetEntity;
                $mapping['cascade']       = $oneToManyAttribute->cascade;
                $mapping['indexBy']       = $oneToManyAttribute->indexBy;
                $mapping['orphanRemoval'] = $oneToManyAttribute->orphanRemoval;
                $mapping['fetch']         = $this->getFetchMode($className, $oneToManyAttribute->fetch);

                $orderByAttribute = $this->reader->getPropertyAttribute($property, Mapping\OrderBy::class);

                if ($orderByAttribute !== null) {
                    $mapping['orderBy'] = $orderByAttribute->value;
                }

                $metadata->mapOneToMany($mapping);
            } elseif ($manyToOneAttribute !== null) {
                $idAttribute = $this->reader->getPropertyAttribute($property, Mapping\Id::class);

                if ($idAttribute !== null) {
                    $mapping['id'] = true;
                }

                $mapping['joinColumns']  = $joinColumns;
                $mapping['cascade']      = $manyToOneAttribute->cascade;
                $mapping['inversedBy']   = $manyToOneAttribute->inversedBy;
                $mapping['targetEntity'] = $manyToOneAttribute->targetEntity;
                $mapping['fetch']        = $this->getFetchMode($className, $manyToOneAttribute->fetch);
                $metadata->mapManyToOne($mapping);
            } elseif ($manyToManyAttribute !== null) {
                $joinTable          = [];
                $joinTableAttribute = $this->reader->getPropertyAttribute($property, Mapping\JoinTable::class);

                if ($joinTableAttribute !== null) {
                    $joinTable = [
                        'name' => $joinTableAttribute->name,
                        'schema' => $joinTableAttribute->schema,
                    ];

                    if ($joinTableAttribute->options) {
                        $joinTable['options'] = $joinTableAttribute->options;
                    }
                }

                foreach ($this->reader->getPropertyAttributeCollection($property, Mapping\JoinColumn::class) as $joinColumn) {
                    $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                }

                foreach ($this->reader->getPropertyAttributeCollection($property, Mapping\InverseJoinColumn::class) as $joinColumn) {
                    $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                }

                $mapping['joinTable']     = $joinTable;
                $mapping['targetEntity']  = $manyToManyAttribute->targetEntity;
                $mapping['mappedBy']      = $manyToManyAttribute->mappedBy;
                $mapping['inversedBy']    = $manyToManyAttribute->inversedBy;
                $mapping['cascade']       = $manyToManyAttribute->cascade;
                $mapping['indexBy']       = $manyToManyAttribute->indexBy;
                $mapping['orphanRemoval'] = $manyToManyAttribute->orphanRemoval;
                $mapping['fetch']         = $this->getFetchMode($className, $manyToManyAttribute->fetch);

                $orderByAttribute = $this->reader->getPropertyAttribute($property, Mapping\OrderBy::class);

                if ($orderByAttribute !== null) {
                    $mapping['orderBy'] = $orderByAttribute->value;
                }

                $metadata->mapManyToMany($mapping);
            } elseif ($embeddedAttribute !== null) {
                $mapping['class']        = $embeddedAttribute->class;
                $mapping['columnPrefix'] = $embeddedAttribute->columnPrefix;

                $metadata->mapEmbedded($mapping);
            }
        }

        // Evaluate AssociationOverrides attribute
        if (isset($classAttributes[Mapping\AssociationOverrides::class])) {
            $associationOverride = $classAttributes[Mapping\AssociationOverrides::class];

            foreach ($associationOverride->overrides as $associationOverride) {
                $override  = [];
                $fieldName = $associationOverride->name;

                // Check for JoinColumn/JoinColumns attributes
                if ($associationOverride->joinColumns) {
                    $joinColumns = [];

                    foreach ($associationOverride->joinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['joinColumns'] = $joinColumns;
                }

                if ($associationOverride->inverseJoinColumns) {
                    $joinColumns = [];

                    foreach ($associationOverride->inverseJoinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['inverseJoinColumns'] = $joinColumns;
                }

                // Check for JoinTable attributes
                if ($associationOverride->joinTable) {
                    $joinTableAnnot = $associationOverride->joinTable;
                    $joinTable      = [
                        'name'      => $joinTableAnnot->name,
                        'schema'    => $joinTableAnnot->schema,
                        'joinColumns' => $override['joinColumns'] ?? [],
                        'inverseJoinColumns' => $override['inverseJoinColumns'] ?? [],
                    ];

                    unset($override['joinColumns'], $override['inverseJoinColumns']);

                    $override['joinTable'] = $joinTable;
                }

                // Check for inversedBy
                if ($associationOverride->inversedBy) {
                    $override['inversedBy'] = $associationOverride->inversedBy;
                }

                // Check for `fetch`
                if ($associationOverride->fetch) {
                    $override['fetch'] = constant(ClassMetadata::class . '::FETCH_' . $associationOverride->fetch);
                }

                $metadata->setAssociationOverride($fieldName, $override);
            }
        }

        // Evaluate AttributeOverrides attribute
        if (isset($classAttributes[Mapping\AttributeOverrides::class])) {
            $attributeOverridesAnnot = $classAttributes[Mapping\AttributeOverrides::class];

            foreach ($attributeOverridesAnnot->overrides as $attributeOverride) {
                $mapping = $this->columnToArray($attributeOverride->name, $attributeOverride->column);

                $metadata->setAttributeOverride($attributeOverride->name, $mapping);
            }
        }

        // Evaluate EntityListeners attribute
        if (isset($classAttributes[Mapping\EntityListeners::class])) {
            $entityListenersAttribute = $classAttributes[Mapping\EntityListeners::class];

            foreach ($entityListenersAttribute->value as $item) {
                $listenerClassName = $metadata->fullyQualifiedClassName($item);

                if (! class_exists($listenerClassName)) {
                    throw MappingException::entityListenerClassNotFound($listenerClassName, $className);
                }

                $hasMapping    = false;
                $listenerClass = new ReflectionClass($listenerClassName);

                foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    assert($method instanceof ReflectionMethod);
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

        // Evaluate #[HasLifecycleCallbacks] attribute
        if (isset($classAttributes[Mapping\HasLifecycleCallbacks::class])) {
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                assert($method instanceof ReflectionMethod);
                foreach ($this->getMethodCallbacks($method) as $value) {
                    $metadata->addLifecycleCallback($value[0], $value[1]);
                }
            }
        }
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param class-string $className The class name.
     * @param string       $fetchMode The fetch mode.
     *
     * @return ClassMetadata::FETCH_* The fetch mode as defined in ClassMetadata.
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
        $callbacks  = [];
        $attributes = $this->reader->getMethodAttributes($method);

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Mapping\PrePersist) {
                $callbacks[] = [$method->name, Events::prePersist];
            }

            if ($attribute instanceof Mapping\PostPersist) {
                $callbacks[] = [$method->name, Events::postPersist];
            }

            if ($attribute instanceof Mapping\PreUpdate) {
                $callbacks[] = [$method->name, Events::preUpdate];
            }

            if ($attribute instanceof Mapping\PostUpdate) {
                $callbacks[] = [$method->name, Events::postUpdate];
            }

            if ($attribute instanceof Mapping\PreRemove) {
                $callbacks[] = [$method->name, Events::preRemove];
            }

            if ($attribute instanceof Mapping\PostRemove) {
                $callbacks[] = [$method->name, Events::postRemove];
            }

            if ($attribute instanceof Mapping\PostLoad) {
                $callbacks[] = [$method->name, Events::postLoad];
            }

            if ($attribute instanceof Mapping\PreFlush) {
                $callbacks[] = [$method->name, Events::preFlush];
            }
        }

        return $callbacks;
    }

    /**
     * Parse the given JoinColumn as array
     *
     * @param Mapping\JoinColumn|Mapping\InverseJoinColumn $joinColumn
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
    private function joinColumnToArray($joinColumn): array
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
     *                   enumType?: class-string,
     *                   options?: mixed[],
     *                   columnName?: string,
     *                   columnDefinition?: string
     *               }
     */
    private function columnToArray(string $fieldName, Mapping\Column $column): array
    {
        $mapping = [
            'fieldName' => $fieldName,
            'type'      => $column->type,
            'scale'     => $column->scale,
            'length'    => $column->length,
            'unique'    => $column->unique,
            'nullable'  => $column->nullable,
            'precision' => $column->precision,
        ];

        if ($column->options) {
            $mapping['options'] = $column->options;
        }

        if (isset($column->name)) {
            $mapping['columnName'] = $column->name;
        }

        if (isset($column->columnDefinition)) {
            $mapping['columnDefinition'] = $column->columnDefinition;
        }

        if ($column->updatable === false) {
            $mapping['notUpdatable'] = true;
        }

        if ($column->insertable === false) {
            $mapping['notInsertable'] = true;
        }

        if ($column->generated !== null) {
            $mapping['generated'] = $this->getGeneratedMode($column->generated);
        }

        if ($column->enumType) {
            $mapping['enumType'] = $column->enumType;
        }

        return $mapping;
    }
}
