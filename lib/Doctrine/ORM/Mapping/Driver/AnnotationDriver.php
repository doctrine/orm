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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;
use Doctrine\ORM\Mapping\MappingException;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @since 2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class AnnotationDriver extends AbstractAnnotationDriver
{
    /**
     * {@inheritDoc}
     */
    protected $entityAnnotationClasses = [
        Annotation\Entity::class           => 1,
        Annotation\MappedSuperclass::class => 2,
    ];

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInterface $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $class   = $metadata->getReflectionClass();

        if ( ! $class) {
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($metadata->name);
        }

        $classAnnotations = $this->reader->getClassAnnotations($class);

        if ($classAnnotations) {
            foreach ($classAnnotations as $key => $annot) {
                if ( ! is_numeric($key)) {
                    continue;
                }

                $classAnnotations[get_class($annot)] = $annot;
            }
        }

        // Evaluate Entity annotation
        switch (true) {
            case isset($classAnnotations[Annotation\Entity::class]):
                $entityAnnot = $classAnnotations[Annotation\Entity::class];

                if ($entityAnnot->repositoryClass !== null) {
                    $builder->setCustomRepositoryClass($entityAnnot->repositoryClass);
                }

                if ($entityAnnot->readOnly) {
                    $builder->setReadOnly();
                }

                break;

            case isset($classAnnotations[Annotation\MappedSuperclass::class]):
                $mappedSuperclassAnnot = $classAnnotations[Annotation\MappedSuperclass::class];

                $builder->setCustomRepositoryClass($mappedSuperclassAnnot->repositoryClass);
                $builder->setMappedSuperClass();
                break;

            case isset($classAnnotations[Annotation\Embeddable::class]):
                $builder->setEmbeddable();
                break;

            default:
                throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate Table annotation
        if (isset($classAnnotations[Annotation\Table::class])) {
            $tableAnnot = $classAnnotations[Annotation\Table::class];

            $builder->setTable($tableAnnot->name, $tableAnnot->schema, $tableAnnot->options);

            foreach ($tableAnnot->indexes as $indexAnnot) {
                $builder->addIndex(
                    $indexAnnot->columns,
                    $indexAnnot->name,
                    $indexAnnot->options,
                    $indexAnnot->flags
                );
            }

            foreach ($tableAnnot->uniqueConstraints as $uniqueConstraintAnnot) {
                $builder->addUniqueConstraint(
                    $uniqueConstraintAnnot->columns,
                    $uniqueConstraintAnnot->name,
                    $uniqueConstraintAnnot->options
                );
            }
        }

        // Evaluate @Cache annotation
        if (isset($classAnnotations[Annotation\Cache::class])) {
            $cacheAnnot = $classAnnotations[Annotation\Cache::class];

            $builder->setCache(
                constant(sprintf('%s::CACHE_USAGE_%s', ClassMetadata::class, $cacheAnnot->usage)),
                $cacheAnnot->region
            );
        }

        // Evaluate NamedNativeQueries annotation
        if (isset($classAnnotations[Annotation\NamedNativeQueries::class])) {
            $namedNativeQueriesAnnot = $classAnnotations[Annotation\NamedNativeQueries::class];

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
        if (isset($classAnnotations[Annotation\SqlResultSetMappings::class])) {
            $sqlResultSetMappingsAnnot = $classAnnotations[Annotation\SqlResultSetMappings::class];

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
                            'column'    => $fieldResultAnnot->column
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
                        'columns'       => $columns
                    ]
                );
            }
        }

        // Evaluate NamedQueries annotation
        if (isset($classAnnotations[Annotation\NamedQueries::class])) {
            $namedQueriesAnnot = $classAnnotations[Annotation\NamedQueries::class];

            if ( ! is_array($namedQueriesAnnot->value)) {
                throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
            }

            foreach ($namedQueriesAnnot->value as $namedQuery) {
                if ( ! ($namedQuery instanceof Annotation\NamedQuery)) {
                    throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
                }

                $builder->addNamedQuery($namedQuery->name, $namedQuery->query);
            }
        }

        // Evaluate InheritanceType annotation
        if (isset($classAnnotations[Annotation\InheritanceType::class])) {
            $inheritanceTypeAnnot = $classAnnotations[Annotation\InheritanceType::class];

            $metadata->setInheritanceType(
                constant(sprintf('%s::INHERITANCE_TYPE_%s', ClassMetadata::class, $inheritanceTypeAnnot->value))
            );

            if ($metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
                $discrColumn = new DiscriminatorColumnMetadata();

                $discrColumn->setTableName($metadata->getTableName());

                // Evaluate DiscriminatorColumn annotation
                if (isset($classAnnotations[Annotation\DiscriminatorColumn::class])) {
                    $discrColumnAnnot = $classAnnotations[Annotation\DiscriminatorColumn::class];

                    $discrColumn->setColumnName($discrColumnAnnot->name);
                    $discrColumn->setColumnDefinition($discrColumnAnnot->columnDefinition);
                    $discrColumn->setType(Type::getType($discrColumnAnnot->type ?: 'string'));
                    $discrColumn->setLength($discrColumnAnnot->length ?: 255);
                } else {
                    $discrColumn->setColumnName('dtype');
                    $discrColumn->setType(Type::getType('string'));
                    $discrColumn->setLength(255);
                }

                $metadata->setDiscriminatorColumn($discrColumn);

                // Evaluate DiscriminatorMap annotation
                if (isset($classAnnotations[Annotation\DiscriminatorMap::class])) {
                    $discrMapAnnot = $classAnnotations[Annotation\DiscriminatorMap::class];

                    $metadata->setDiscriminatorMap($discrMapAnnot->value);
                }
            }
        }


        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations[Annotation\ChangeTrackingPolicy::class])) {
            $changeTrackingAnnot = $classAnnotations[Annotation\ChangeTrackingPolicy::class];

            $metadata->setChangeTrackingPolicy(
                constant(sprintf('%s::CHANGETRACKING_%s', ClassMetadata::class, $changeTrackingAnnot->value))
            );
        }

        // Evaluate annotations on properties/fields
        /* @var $reflProperty \ReflectionProperty */
        foreach ($class->getProperties() as $reflProperty) {
            /*if ($reflProperty->getDeclaringClass()->name !== $class->name) {
                continue;
            }*/

            if (($metadata->isMappedSuperclass && ! $reflProperty->isPrivate())
                || (($property = $metadata->getProperty($reflProperty->name)) !== null && $metadata->isInheritedProperty($property->getName()))
                || $metadata->isInheritedAssociation($reflProperty->name)
                || $metadata->isInheritedEmbeddedClass($reflProperty->name)) {
                continue;
            }

            $mapping = [];
            $mapping['fieldName'] = $reflProperty->getName();

            // Evaluate @Cache annotation
            if (($cacheAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Cache::class)) !== null) {
                $mapping['cache'] = $metadata->getAssociationCacheDefaults(
                    $mapping['fieldName'],
                    [
                        'usage'  => constant(sprintf('%s::CACHE_USAGE_%s', ClassMetadata::class, $cacheAnnot->usage)),
                        'region' => $cacheAnnot->region,
                    ]
                );
            }
            // Check for JoinColumn/JoinColumns annotations
            $joinColumns = [];

            if ($joinColumnAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumn::class)) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAnnot);
            } else if ($joinColumnsAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumns::class)) {
                foreach ($joinColumnsAnnot->value as $joinColumn) {
                    $joinColumns[] = $this->joinColumnToArray($joinColumn);
                }
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            if ($columnAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Column::class)) {
                if ($columnAnnot->type == null) {
                    throw MappingException::propertyTypeIsRequired($className, $reflProperty->getName());
                }

                $mapping = $this->columnToArray($columnAnnot);

                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $mapping['id'] = true;
                }

                if ($generatedValueAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\GeneratedValue::class)) {
                    $metadata->setIdGeneratorType(
                        constant(sprintf('%s::GENERATOR_TYPE_%s', ClassMetadata::class, $generatedValueAnnot->strategy))
                    );
                }

                $property = $metadata->addProperty($reflProperty->getName(), Type::getType($columnAnnot->type), $mapping);

                if ($this->reader->getPropertyAnnotation($reflProperty, Annotation\Version::class)) {
                    $metadata->setVersionProperty($property);
                }

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\SequenceGenerator::class)) {
                    $metadata->setSequenceGeneratorDefinition(
                        [
                            'sequenceName' => $seqGeneratorAnnot->sequenceName,
                            'allocationSize' => $seqGeneratorAnnot->allocationSize,
                            'initialValue' => $seqGeneratorAnnot->initialValue
                        ]
                    );
                } else if ($this->reader->getPropertyAnnotation($reflProperty, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                } else if ($customGeneratorAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\CustomIdGenerator::class)) {
                    $metadata->setCustomGeneratorDefinition(['class' => $customGeneratorAnnot->class]);
                }
            } else if ($oneToOneAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OneToOne::class)) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $mapping['id'] = true;
                }

                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['inversedBy'] = $oneToOneAnnot->inversedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $mapping['orphanRemoval'] = $oneToOneAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToOneAnnot->fetch);
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OneToMany::class)) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $mapping['indexBy'] = $oneToManyAnnot->indexBy;
                $mapping['orphanRemoval'] = $oneToManyAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToManyAnnot->fetch);

                if ($orderByAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\ManyToOne::class)) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $mapping['id'] = true;
                }

                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['inversedBy'] = $manyToOneAnnot->inversedBy;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnot->fetch);
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\ManyToMany::class)) {
                $joinTable = [];

                if ($joinTableAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinTable::class)) {
                    $joinTable = [
                        'name' => $joinTableAnnot->name,
                        'schema' => $joinTableAnnot->schema
                    ];

                    foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }
                }

                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $mapping['inversedBy'] = $manyToManyAnnot->inversedBy;
                $mapping['cascade'] = $manyToManyAnnot->cascade;
                $mapping['indexBy'] = $manyToManyAnnot->indexBy;
                $mapping['orphanRemoval'] = $manyToManyAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToManyAnnot->fetch);

                if ($orderByAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OrderBy::class)) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapManyToMany($mapping);
            } else if ($embeddedAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Embedded::class)) {
                $mapping['class'] = $embeddedAnnot->class;
                $mapping['columnPrefix'] = $embeddedAnnot->columnPrefix;

                $metadata->mapEmbedded($mapping);
            }
        }

        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations[Annotation\AssociationOverrides::class])) {
            $associationOverridesAnnot = $classAnnotations[Annotation\AssociationOverrides::class];

            foreach ($associationOverridesAnnot->value as $associationOverride) {
                $override   = [];
                $fieldName  = $associationOverride->name;

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
                        'schema'    => $joinTableAnnot->schema
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
        if (isset($classAnnotations[Annotation\AttributeOverrides::class])) {
            $attributeOverridesAnnot = $classAnnotations[Annotation\AttributeOverrides::class];

            foreach ($attributeOverridesAnnot->value as $attributeOverrideAnnot) {
                $attributeOverride = $this->columnToArray($attributeOverrideAnnot->column);

                $metadata->setAttributeOverride($attributeOverrideAnnot->name, $attributeOverride);
            }
        }

        // Evaluate EntityListeners annotation
        if (isset($classAnnotations[Annotation\EntityListeners::class])) {
            $entityListenersAnnot = $classAnnotations[Annotation\EntityListeners::class];

            foreach ($entityListenersAnnot->value as $item) {
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
        if (isset($classAnnotations[Annotation\HasLifecycleCallbacks::class])) {
            /* @var $method \ReflectionMethod */
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
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
        $fetchModeConstant = sprintf('%s::FETCH_%s', ClassMetadata::class, $fetchMode);

        if ( ! defined($fetchModeConstant)) {
            throw MappingException::invalidFetchMode($className, $fetchMode);
        }

        return constant($fetchModeConstant);
    }

    /**
     * Parses the given method.
     *
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    private function getMethodCallbacks(\ReflectionMethod $method)
    {
        $callbacks   = [];
        $annotations = $this->reader->getMethodAnnotations($method);

        foreach ($annotations as $annot) {
            if ($annot instanceof Annotation\PrePersist) {
                $callbacks[] = [$method->name, Events::prePersist];
            }

            if ($annot instanceof Annotation\PostPersist) {
                $callbacks[] = [$method->name, Events::postPersist];
            }

            if ($annot instanceof Annotation\PreUpdate) {
                $callbacks[] = [$method->name, Events::preUpdate];
            }

            if ($annot instanceof Annotation\PostUpdate) {
                $callbacks[] = [$method->name, Events::postUpdate];
            }

            if ($annot instanceof Annotation\PreRemove) {
                $callbacks[] = [$method->name, Events::preRemove];
            }

            if ($annot instanceof Annotation\PostRemove) {
                $callbacks[] = [$method->name, Events::postRemove];
            }

            if ($annot instanceof Annotation\PostLoad) {
                $callbacks[] = [$method->name, Events::postLoad];
            }

            if ($annot instanceof Annotation\PreFlush) {
                $callbacks[] = [$method->name, Events::preFlush];
            }
        }

        return $callbacks;
    }

    /**
     * Parse the given JoinColumn as array
     *
     * @param Annotation\JoinColumn $joinColumn
     *
     * @return array
     */
    private function joinColumnToArray(Annotation\JoinColumn $joinColumn)
    {
        return [
            'name' => $joinColumn->name,
            'unique' => $joinColumn->unique,
            'nullable' => $joinColumn->nullable,
            'onDelete' => $joinColumn->onDelete ? strtoupper($joinColumn->onDelete) : $joinColumn->onDelete,
            'columnDefinition' => $joinColumn->columnDefinition,
            'referencedColumnName' => $joinColumn->referencedColumnName,
        ];
    }

    /**
     * Parse the given Column as array
     *
     * @param string            $fieldName
     * @param Annotation\Column $column
     *
     * @return array
     */
    private function columnToArray(Annotation\Column $column)
    {
        $mapping = [
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

    /**
     * Factory method for the Annotation Driver.
     *
     * @param array|string          $paths
     * @param AnnotationReader|null $reader
     *
     * @return AnnotationDriver
     */
    static public function create($paths = [], AnnotationReader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}
