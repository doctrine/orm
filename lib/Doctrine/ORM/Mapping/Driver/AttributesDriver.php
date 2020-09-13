<?php

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\AnnotationDriver;

class AttributesDriver extends AnnotationDriver
{
    /**
     * {@inheritDoc}
     */
    protected $entityAnnotationClasses = [
        Mapping\Entity::class => 1,
        Mapping\MappedSuperclass::class => 2,
    ];

    public function __construct(array $paths)
    {
        parent::__construct(new AttributeReader(), $paths);
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        $reflectionClass = $metadata->getReflectionClass();

        $classAttributes = $this->reader->getClassAnnotations($reflectionClass);

        // Evaluate Entity annotation
        if (isset($classAttributes[Mapping\Entity::class])) {
            $entityAttribute = $classAttributes[Mapping\Entity::class];
            if ($entityAttribute->repositoryClass !== null) {
                $metadata->setCustomRepositoryClass($entityAttribute->repositoryClass);
            }

            if ($entityAttribute->readOnly) {
                $metadata->markReadOnly();
            }
        } else if (isset($classAttributes[Mapping\MappedSuperclass::class])) {
            $mappedSuperclassAttribute = $classAttributes[Mapping\MappedSuperclass::class];

            $metadata->setCustomRepositoryClass($mappedSuperclassAttribute->repositoryClass);
            $metadata->isMappedSuperclass = true;
        } else if (isset($classAttributes[Mapping\Embeddable::class])) {
            $metadata->isEmbeddedClass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate Table annotation
        if (isset($classAttributes[Mapping\Table::class])) {
            $tableAnnot   = $classAttributes[Mapping\Table::class];
            $primaryTable = [
                'name'   => $tableAnnot->name,
                'schema' => $tableAnnot->schema
            ];

            if (isset($classAttributes[Mapping\Index::class])) {
                foreach ($classAttributes[Mapping\Index::class] as $indexAnnot) {
                    $index = ['columns' => $indexAnnot->columns];

                    if ( ! empty($indexAnnot->flags)) {
                        $index['flags'] = $indexAnnot->flags;
                    }

                    if ( ! empty($indexAnnot->options)) {
                        $index['options'] = $indexAnnot->options;
                    }

                    if ( ! empty($indexAnnot->name)) {
                        $primaryTable['indexes'][$indexAnnot->name] = $index;
                    } else {
                        $primaryTable['indexes'][] = $index;
                    }
                }
            }

            if (isset($classAttributes[Mapping\UniqueConstraint::class])) {
                foreach ($classAttributes[Mapping\UniqueConstraint::class] as $uniqueConstraintAnnot) {
                    $uniqueConstraint = ['columns' => $uniqueConstraintAnnot->columns];

                    if ( ! empty($uniqueConstraintAnnot->options)) {
                        $uniqueConstraint['options'] = $uniqueConstraintAnnot->options;
                    }

                    if ( ! empty($uniqueConstraintAnnot->name)) {
                        $primaryTable['uniqueConstraints'][$uniqueConstraintAnnot->name] = $uniqueConstraint;
                    } else {
                        $primaryTable['uniqueConstraints'][] = $uniqueConstraint;
                    }
                }
            }

            if ($tableAnnot->options) {
                $primaryTable['options'] = $tableAnnot->options;
            }

            $metadata->setPrimaryTable($primaryTable);
        }

        // Evaluate @Cache annotation
        if (isset($classAttributes[Mapping\Cache::class])) {
            $cacheAttribute = $classAttributes[Mapping\Cache::class];
            $cacheMap   = [
                'region' => $cacheAttribute->region,
                'usage'  => constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAttribute->usage),
            ];

            $metadata->enableCache($cacheMap);
        }

        // Evaluate InheritanceType annotation
        if (isset($classAttributes[Mapping\InheritanceType::class])) {
            $inheritanceTypeAttribute = $classAttributes[Mapping\InheritanceType::class];

            $metadata->setInheritanceType(
                constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAttribute->value)
            );

            if ($metadata->inheritanceType != Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
                // Evaluate DiscriminatorColumn annotation
                if (isset($classAttributes[Mapping\DiscriminatorColumn::class])) {
                    $discrColumnAttribute = $classAttributes[Mapping\DiscriminatorColumn::class];

                    $metadata->setDiscriminatorColumn(
                        [
                            'name'             => $discrColumnAttribute->name,
                            'type'             => $discrColumnAttribute->type ?: 'string',
                            'length'           => $discrColumnAttribute->length ?: 255,
                            'columnDefinition' => $discrColumnAttribute->columnDefinition,
                        ]
                    );
                } else {
                    $metadata->setDiscriminatorColumn(['name' => 'dtype', 'type' => 'string', 'length' => 255]);
                }

                // Evaluate DiscriminatorMap annotation
                if (isset($classAttributes[Mapping\DiscriminatorMap::class])) {
                    $discrMapAttribute = $classAttributes[Mapping\DiscriminatorMap::class];
                    $metadata->setDiscriminatorMap($discrMapAttribute->value);
                }
            }
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAttributes[Mapping\ChangeTrackingPolicy::class])) {
            $changeTrackingAttribute = $classAttributes[Mapping\ChangeTrackingPolicy::class];
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAttribute->value));
        }

        // Evaluate annotations on properties/fields
        /* @var $property \ReflectionProperty */
        foreach ($reflectionClass->getProperties() as $property) {
            if ($metadata->isMappedSuperclass && ! $property->isPrivate()
                ||
                $metadata->isInheritedField($property->name)
                ||
                $metadata->isInheritedAssociation($property->name)
                ||
                $metadata->isInheritedEmbeddedClass($property->name)) {
                continue;
            }

            $mapping = [];
            $mapping['fieldName'] = $property->getName();

            // Evaluate @Cache annotation
            if (($cacheAttribute = $this->reader->getPropertyAnnotation($property, Mapping\Cache::class)) !== null) {
                $mapping['cache'] = $metadata->getAssociationCacheDefaults(
                    $mapping['fieldName'],
                    [
                        'usage'  => constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $cacheAttribute->usage),
                        'region' => $cacheAttribute->region,
                    ]
                );
            }
            // Check for JoinColumn/JoinColumns annotations
            $joinColumns = [];

            if ($joinColumnAttribute = $this->reader->getPropertyAnnotation($property, Mapping\JoinColumn::class)) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAttribute);
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            if ($columnAttribute = $this->reader->getPropertyAnnotation($property, Mapping\Column::class)) {
                if ($columnAttribute->type == null) {
                    throw MappingException::propertyTypeIsRequired($className, $property->getName());
                }

                $mapping = $this->columnToArray($property->getName(), $columnAttribute);

                if ($this->reader->getPropertyAnnotation($property, Mapping\Id::class)) {
                    $mapping['id'] = true;
                }

                if ($generatedValueAttribute = $this->reader->getPropertyAnnotation($property, Mapping\GeneratedValue::class)) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAttribute->strategy));
                }

                if ($this->reader->getPropertyAnnotation($property, Mapping\Version::class)) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAttribute = $this->reader->getPropertyAnnotation($property, Mapping\SequenceGenerator::class)) {
                    $metadata->setSequenceGeneratorDefinition(
                        [
                            'sequenceName' => $seqGeneratorAttribute->sequenceName,
                            'allocationSize' => $seqGeneratorAttribute->allocationSize,
                            'initialValue' => $seqGeneratorAttribute->initialValue
                        ]
                    );
                } else if ($this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                } else if ($customGeneratorAttribute = $this->reader->getPropertyAnnotation($property, Mapping\CustomIdGenerator::class)) {
                    $metadata->setCustomGeneratorDefinition(
                        [
                            'class' => $customGeneratorAttribute->class
                        ]
                    );
                }
            } else if ($oneToOneAttribute = $this->reader->getPropertyAnnotation($property, Mapping\OneToOne::class)) {
                if ($this->reader->getPropertyAnnotation($property, Mapping\Id::class)) {
                    $mapping['id'] = true;
                }

                $mapping['targetEntity'] = $oneToOneAttribute->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAttribute->mappedBy;
                $mapping['inversedBy'] = $oneToOneAttribute->inversedBy;
                $mapping['cascade'] = $oneToOneAttribute->cascade;
                $mapping['orphanRemoval'] = $oneToOneAttribute->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToOneAttribute->fetch);
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAttribute = $this->reader->getPropertyAnnotation($property, Mapping\OneToMany::class)) {
                $mapping['mappedBy'] = $oneToManyAttribute->mappedBy;
                $mapping['targetEntity'] = $oneToManyAttribute->targetEntity;
                $mapping['cascade'] = $oneToManyAttribute->cascade;
                $mapping['indexBy'] = $oneToManyAttribute->indexBy;
                $mapping['orphanRemoval'] = $oneToManyAttribute->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToManyAttribute->fetch);

                if ($orderByAttribute = $this->reader->getPropertyAnnotation($property, Mapping\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAttribute->value;
                }

                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAttribute = $this->reader->getPropertyAnnotation($property, Mapping\ManyToOne::class)) {
                if ($idAttribute = $this->reader->getPropertyAnnotation($property, Mapping\Id::class)) {
                    $mapping['id'] = true;
                }

                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAttribute->cascade;
                $mapping['inversedBy'] = $manyToOneAttribute->inversedBy;
                $mapping['targetEntity'] = $manyToOneAttribute->targetEntity;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAttribute->fetch);
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAttribute = $this->reader->getPropertyAnnotation($property, Mapping\ManyToMany::class)) {
                $joinTable = [];

                if ($joinTableAttribute = $this->reader->getPropertyAnnotation($property, Mapping\JoinTable::class)) {
                    $joinTable = [
                        'name' => $joinTableAttribute->name,
                        'schema' => $joinTableAttribute->schema
                    ];

                    foreach ($this->reader->getPropertyAnnotation($property, Mapping\JoinColumn::class) as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($this->reader->getPropertyAnnotation($property, Mapping\InverseJoinColumn::class) as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }
                }

                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAttribute->targetEntity;
                $mapping['mappedBy'] = $manyToManyAttribute->mappedBy;
                $mapping['inversedBy'] = $manyToManyAttribute->inversedBy;
                $mapping['cascade'] = $manyToManyAttribute->cascade;
                $mapping['indexBy'] = $manyToManyAttribute->indexBy;
                $mapping['orphanRemoval'] = $manyToManyAttribute->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToManyAttribute->fetch);

                if ($orderByAttribute = $this->reader->getPropertyAnnotation($property, Mapping\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAttribute->value;
                }

                $metadata->mapManyToMany($mapping);
            } else if ($embeddedAttribute = $this->reader->getPropertyAnnotation($property, Mapping\Embedded::class)) {
                $mapping['class'] = $embeddedAttribute->class;
                $mapping['columnPrefix'] = $embeddedAttribute->columnPrefix;

                $metadata->mapEmbedded($mapping);
            }
        }

        // Evaluate AttributeOverrides annotation
        if (isset($classAttributes[Mapping\AttributeOverride::class])) {

            foreach ($classAttributes[Mapping\AttributeOverride::class] as $attributeOverrideAttribute) {
                $attributeOverride = $this->columnToArray($attributeOverrideAttribute->name, $attributeOverrideAttribute->column);

                $metadata->setAttributeOverride($attributeOverrideAttribute->name, $attributeOverride);
            }
        }

        // Evaluate EntityListeners annotation
        if (isset($classAttributes[Mapping\EntityListeners::class])) {
            $entityListenersAttribute = $classAttributes[Mapping\EntityListeners::class];

            foreach ($entityListenersAttribute->value as $item) {
                $listenerClassName = $metadata->fullyQualifiedClassName($item);

                if ( ! class_exists($listenerClassName)) {
                    throw MappingException::entityListenerClassNotFound($listenerClassName, $className);
                }

                $hasMapping     = false;
                $listenerClass  = new \ReflectionClass($listenerClassName);

                /* @var $method \ReflectionMethod */
                foreach ($listenerClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    // find method callbacks.
                    $callbacks  = $this->getMethodCallbacks($method);
                    $hasMapping = $hasMapping ?: ( ! empty($callbacks));

                    foreach ($callbacks as $value) {
                        $metadata->addEntityListener($value[1], $listenerClassName, $value[0]);
                    }
                }

                // Evaluate the listener using naming convention.
                if ( ! $hasMapping ) {
                    EntityListenerBuilder::bindEntityListener($metadata, $listenerClassName);
                }
            }
        }

        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAttributes[Mapping\HasLifecycleCallbacks::class])) {
            /* @var $method \ReflectionMethod */
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($this->getMethodCallbacks($method) as $value) {
                    $metadata->addLifecycleCallback($value[0], $value[1]);
                }
            }
        }
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param string $className The class name.
     * @param string $fetchMode The fetch mode.
     *
     * @return integer The fetch mode as defined in ClassMetadata.
     *
     * @throws MappingException If the fetch mode is not valid.
     */
    private function getFetchMode($className, $fetchMode)
    {
        if ( ! defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
            throw MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode);
    }

    /**
     * Parses the given method.
     *
     * @param \ReflectionMethod $method
     *
     * @return callable[]
     */
    private function getMethodCallbacks(\ReflectionMethod $method)
    {
        $callbacks   = [];
        $attributes = $this->reader->getMethodAnnotations($method);

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
     * @param Mapping\JoinColumn $joinColumn
     *
     * @return mixed[]
     *
     * @psalm-return array{
     *                   name: string,
     *                   unique: bool,
     *                   nullable: bool,
     *                   onDelete: mixed,
     *                   columnDefinition: string,
     *                   referencedColumnName: string
     *               }
     */
    private function joinColumnToArray(Mapping\JoinColumn $joinColumn)
    {
        return [
            'name' => $joinColumn->name,
            'unique' => $joinColumn->unique,
            'nullable' => $joinColumn->nullable,
            'onDelete' => $joinColumn->onDelete,
            'columnDefinition' => $joinColumn->columnDefinition,
            'referencedColumnName' => $joinColumn->referencedColumnName,
        ];
    }

    /**
     * Parse the given Column as array
     *
     * @param string $fieldName
     * @param Mapping\Column $column
     *
     * @return mixed[]
     *
     * @psalm-return array{
     *                   fieldName: string,
     *                   type: mixed,
     *                   scale: int,
     *                   length: int,
     *                   unique: bool,
     *                   nullable: bool,
     *                   precision: int,
     *                   options?: mixed[],
     *                   columnName?: string,
     *                   columnDefinition?: string
     *               }
     */
    private function columnToArray($fieldName, Mapping\Column $column)
    {
        $mapping = [
            'fieldName' => $fieldName,
            'type'      => $column->type,
            'scale'     => $column->scale,
            'length'    => $column->length,
            'unique'    => $column->unique,
            'nullable'  => $column->nullable,
            'precision' => $column->precision
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

        return $mapping;
    }
}