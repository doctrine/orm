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
use Doctrine\ORM\Mapping\Builder\CacheMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\Builder\TableMetadataBuilder;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\CacheUsage;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\Property;
use Doctrine\ORM\Mapping\VersionFieldMetadata;

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

        foreach ($classAnnotations as $key => $annot) {
            if ( ! is_numeric($key)) {
                continue;
            }

            $classAnnotations[get_class($annot)] = $annot;
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

            if (! empty($tableAnnot->name)) {
                $metadata->table->setName($tableAnnot->name);
            }

            if (! empty($tableAnnot->schema)) {
                $metadata->table->setSchema($tableAnnot->schema);
            }

            foreach ($tableAnnot->options as $optionName => $optionValue) {
                $metadata->table->addOption($optionName, $optionValue);
            }

            foreach ($tableAnnot->indexes as $indexAnnot) {
                $metadata->table->addIndex([
                    'name'    => $indexAnnot->name,
                    'columns' => $indexAnnot->columns,
                    'unique'  => $indexAnnot->unique,
                    'options' => $indexAnnot->options,
                    'flags'   => $indexAnnot->flags,
                ]);
            }

            foreach ($tableAnnot->uniqueConstraints as $uniqueConstraintAnnot) {
                $metadata->table->addUniqueConstraint([
                    'name'    => $uniqueConstraintAnnot->name,
                    'columns' => $uniqueConstraintAnnot->columns,
                    'options' => $uniqueConstraintAnnot->options,
                    'flags'   => $uniqueConstraintAnnot->flags,
                ]);
            }
        }

        // Evaluate @Cache annotation
        if (isset($classAnnotations[Annotation\Cache::class])) {
            $cacheAnnot = $classAnnotations[Annotation\Cache::class];
            $cache      = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $metadata);

            $metadata->setCache($cache);
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
                $sqlResultSetMapping = $this->convertSqlResultSetMapping($resultSetMapping);

                $metadata->addSqlResultSetMapping($sqlResultSetMapping);
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
                constant(sprintf('%s::%s', InheritanceType::class, $inheritanceTypeAnnot->value))
            );

            if ($metadata->inheritanceType !== InheritanceType::NONE) {
                $discriminatorColumnBuilder = new DiscriminatorColumnMetadataBuilder();

                $discriminatorColumnBuilder->withTableName($metadata->getTableName());

                // Evaluate DiscriminatorColumn annotation
                if (isset($classAnnotations[Annotation\DiscriminatorColumn::class])) {
                    /** @var Annotation\DiscriminatorColumn $discriminatorColumnAnnotation */
                    $discriminatorColumnAnnotation = $classAnnotations[Annotation\DiscriminatorColumn::class];

                    $discriminatorColumnBuilder->withColumnName($discriminatorColumnAnnotation->name);

                    if (! empty($discriminatorColumnAnnotation->columnDefinition)) {
                        $discriminatorColumnBuilder->withColumnDefinition($discriminatorColumnAnnotation->columnDefinition);
                    }

                    if (! empty($discriminatorColumnAnnotation->type)) {
                        $discriminatorColumnBuilder->withType(Type::getType($discriminatorColumnAnnotation->type));
                    }

                    if (! empty($discriminatorColumnAnnotation->length)) {
                        $discriminatorColumnBuilder->withLength($discriminatorColumnAnnotation->length);
                    }
                }

                $discriminatorColumn = $discriminatorColumnBuilder->build();

                $metadata->setDiscriminatorColumn($discriminatorColumn);

                // Evaluate DiscriminatorMap annotation
                if (isset($classAnnotations[Annotation\DiscriminatorMap::class])) {
                    $discriminatorMapAnnotation = $classAnnotations[Annotation\DiscriminatorMap::class];

                    $metadata->setDiscriminatorMap($discriminatorMapAnnotation->value);
                }
            }
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations[Annotation\ChangeTrackingPolicy::class])) {
            $changeTrackingAnnot = $classAnnotations[Annotation\ChangeTrackingPolicy::class];

            $metadata->setChangeTrackingPolicy(
                constant(sprintf('%s::%s', ChangeTrackingPolicy::class, $changeTrackingAnnot->value))
            );
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

        // Evaluate annotations on properties/fields
        /* @var $reflProperty \ReflectionProperty */
        foreach ($class->getProperties() as $reflProperty) {
            if ($reflProperty->getDeclaringClass()->name !== $class->name) {
                continue;
            }

//            $property = $this->convertProperty($reflProperty);
//
//            $metadata->addProperty($property);

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            $fieldName = $reflProperty->getName();

            if ($columnAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Column::class)) {
                if ($columnAnnot->type == null) {
                    throw MappingException::propertyTypeIsRequired($className, $fieldName);
                }

                $isFieldVersioned = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Version::class) !== null;
                $fieldMetadata    = $this->convertColumnAnnotationToFieldMetadata($columnAnnot, $fieldName, $isFieldVersioned);

                // Check for Id
                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $fieldMetadata->setPrimaryKey(true);
                }

                // Check for GeneratedValue strategy
                if ($generatedValueAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\GeneratedValue::class)) {
                    $strategy = strtoupper($generatedValueAnnot->strategy);

                    $metadata->setIdGeneratorType(constant(sprintf('%s::%s', GeneratorType::class, $strategy)));
                }

                // Check for CustomGenerator/SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\SequenceGenerator::class)) {
                    $metadata->setGeneratorDefinition(
                        [
                            'sequenceName'   => $seqGeneratorAnnot->sequenceName,
                            'allocationSize' => $seqGeneratorAnnot->allocationSize,
                        ]
                    );
                } else if ($this->reader->getPropertyAnnotation($reflProperty, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                } else if ($customGeneratorAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\CustomIdGenerator::class)) {
                    $metadata->setGeneratorDefinition(
                        [
                            'class'     => $customGeneratorAnnot->class,
                            'arguments' => $customGeneratorAnnot->arguments,
                        ]
                    );
                }

                $metadata->addProperty($fieldMetadata);

                // Check for Version
                if ($this->reader->getPropertyAnnotation($reflProperty, Annotation\Version::class)) {
                    $metadata->setVersionProperty($fieldMetadata);
                }

                continue;
            }

            $mapping = [];
            $mapping['fieldName'] = $fieldName;

            // Evaluate @Cache annotation
            if (($cacheAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Cache::class)) !== null) {
                $mapping['cache'] = $this->convertCacheAnnotationToCacheMetadata(
                    $cacheAnnot,
                    $metadata,
                    $fieldName
                );
            }

            if ($oneToOneAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OneToOne::class)) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $mapping['id'] = true;
                }

                // Check for JoinColumn/JoinColumns annotations
                $joinColumns = [];

                if ($joinColumnAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumn::class)) {
                    $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);
                } else if ($joinColumnsAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumns::class)) {
                    foreach ($joinColumnsAnnot->value as $joinColumn) {
                        $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumn);
                    }
                }

                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['inversedBy'] = $oneToOneAnnot->inversedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $mapping['orphanRemoval'] = $oneToOneAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToOneAnnot->fetch);

                $metadata->mapOneToOne($mapping);

                continue;
            }

            if ($oneToManyAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\OneToMany::class)) {
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

                continue;
            }

            if ($manyToOneAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\ManyToOne::class)) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Id::class)) {
                    $mapping['id'] = true;
                }

                // Check for JoinColumn/JoinColumns annotations
                $joinColumns = [];

                if ($joinColumnAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumn::class)) {
                    $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);
                } else if ($joinColumnsAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinColumns::class)) {
                    foreach ($joinColumnsAnnot->value as $joinColumn) {
                        $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumn);
                    }
                }

                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['inversedBy'] = $manyToOneAnnot->inversedBy;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnot->fetch);

                $metadata->mapManyToOne($mapping);

                continue;
            }

            if ($manyToManyAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\ManyToMany::class)) {
                $joinTable = new JoinTableMetadata();

                if ($joinTableAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\JoinTable::class)) {
                    if (! empty($joinTableAnnot->name)) {
                        $joinTable->setName($joinTableAnnot->name);
                    }

                    if (! empty($joinTableAnnot->schema)) {
                        $joinTable->setSchema($joinTableAnnot->schema);
                    }

                    foreach ($joinTableAnnot->joinColumns as $joinColumnAnnot) {
                        $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);

                        $joinTable->addJoinColumn($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumnAnnot) {
                        $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);

                        $joinTable->addInverseJoinColumn($joinColumn);
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

                continue;
            }

            if ($embeddedAnnot = $this->reader->getPropertyAnnotation($reflProperty, Annotation\Embedded::class)) {
                $mapping['class'] = $embeddedAnnot->class;
                $mapping['columnPrefix'] = $embeddedAnnot->columnPrefix;

                $metadata->mapEmbedded($mapping);

                continue;
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
                        $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumn);
                    }

                    $override['joinColumns'] = $joinColumns;
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTableAnnot = $associationOverride->joinTable;
                    $joinTable      = new JoinTableMetadata();

                    if (!empty($joinTableAnnot->name)) {
                        $joinTable->setName($joinTableAnnot->name);
                    }

                    if (!empty($joinTableAnnot->schema)) {
                        $joinTable->setSchema($joinTableAnnot->schema);
                    }

                    foreach ($joinTableAnnot->joinColumns as $joinColumnAnnot) {
                        $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);

                        $joinTable->addJoinColumn($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumnAnnot) {
                        $joinColumn = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);

                        $joinTable->addInverseJoinColumn($joinColumn);
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
                $fieldMetadata = $this->convertColumnAnnotationToFieldMetadata(
                    $attributeOverrideAnnot->column,
                    $attributeOverrideAnnot->name,
                    false
                );

                $metadata->setAttributeOverride($fieldMetadata);
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
        $fetchModeConstant = sprintf('%s::%s', FetchMode::class, $fetchMode);

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
     * @param Annotation\SqlResultSetMapping $resultSetMapping
     *
     * @return array
     */
    private function convertSqlResultSetMapping(Annotation\SqlResultSetMapping $resultSetMapping)
    {
        $entities = [];

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

        $columns = [];

        foreach ($resultSetMapping->columns as $columnResultAnnot) {
            $columns[] = [
                'name' => $columnResultAnnot->name,
            ];
        }

        return [
            'name'     => $resultSetMapping->name,
            'entities' => $entities,
            'columns'  => $columns
        ];
    }

    /**
     * Parse the given Column as FieldMetadata
     *
     * @param Annotation\Column $columnAnnot
     * @param string            $fieldName
     * @param bool              $isVersioned
     *
     * @return FieldMetadata
     */
    private function convertColumnAnnotationToFieldMetadata(Annotation\Column $columnAnnot, string $fieldName, bool $isVersioned)
    {
        $fieldMetadata = $isVersioned
            ? new VersionFieldMetadata($fieldName)
            : new FieldMetadata($fieldName)
        ;

        $fieldMetadata->setType(Type::getType($columnAnnot->type));

        if (! empty($columnAnnot->name)) {
            $fieldMetadata->setColumnName($columnAnnot->name);
        }

        if (! empty($columnAnnot->columnDefinition)) {
            $fieldMetadata->setColumnDefinition($columnAnnot->columnDefinition);
        }

        if (! empty($columnAnnot->length)) {
            $fieldMetadata->setLength($columnAnnot->length);
        }

        if ($columnAnnot->options) {
            $fieldMetadata->setOptions($columnAnnot->options);
        }

        $fieldMetadata->setScale($columnAnnot->scale);
        $fieldMetadata->setPrecision($columnAnnot->precision);
        $fieldMetadata->setNullable($columnAnnot->nullable);
        $fieldMetadata->setUnique($columnAnnot->unique);

        return $fieldMetadata;
    }

    /**
     * Parse the given JoinColumn as JoinColumnMetadata
     *
     * @param Annotation\JoinColumn $joinColumnAnnot
     *
     * @return JoinColumnMetadata
     */
    private function convertJoinColumnAnnotationToJoinColumnMetadata(Annotation\JoinColumn $joinColumnAnnot)
    {
        $joinColumn = new JoinColumnMetadata();

        // @todo Remove conditionals for name and referencedColumnName once naming strategy is brought into drivers
        if (! empty($joinColumnAnnot->name)) {
            $joinColumn->setColumnName($joinColumnAnnot->name);
        }

        if (! empty($joinColumnAnnot->referencedColumnName)) {
            $joinColumn->setReferencedColumnName($joinColumnAnnot->referencedColumnName);
        }

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
     * Parse the given Cache as CacheMetadata
     *
     * @param Annotation\Cache $cacheAnnot
     * @param ClassMetadata    $metadata
     * @param null|string      $fieldName
     *
     * @return CacheMetadata
     */
    private function convertCacheAnnotationToCacheMetadata(Annotation\Cache $cacheAnnot, ClassMetadata $metadata, $fieldName = null)
    {
        $baseRegion    = strtolower(str_replace('\\', '_', $metadata->rootEntityName));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');
        $cacheBuilder  = new CacheMetadataBuilder();

        $cacheBuilder
            ->withUsage(constant(sprintf('%s::%s', CacheUsage::class, $cacheAnnot->usage)))
            ->withRegion($cacheAnnot->region ?: $defaultRegion)
        ;

        return $cacheBuilder->build();
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
