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
use Doctrine\ORM\Mapping;

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
                    $builder->asReadOnly();
                }

                break;

            case isset($classAnnotations[Annotation\MappedSuperclass::class]):
                $mappedSuperclassAnnot = $classAnnotations[Annotation\MappedSuperclass::class];

                $builder->setCustomRepositoryClass($mappedSuperclassAnnot->repositoryClass);
                $builder->asMappedSuperClass();
                break;

            case isset($classAnnotations[Annotation\Embeddable::class]):
                $builder->asEmbeddable();
                break;

            default:
                throw Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
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

            $builder->withCache($cache);
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
                constant(sprintf('%s::%s', Mapping\InheritanceType::class, $inheritanceTypeAnnot->value))
            );

            if ($metadata->inheritanceType !== Mapping\InheritanceType::NONE) {
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
                constant(sprintf('%s::%s', Mapping\ChangeTrackingPolicy::class, $changeTrackingAnnot->value))
            );
        }

        // Evaluate EntityListeners annotation
        if (isset($classAnnotations[Annotation\EntityListeners::class])) {
            $entityListenersAnnot = $classAnnotations[Annotation\EntityListeners::class];

            foreach ($entityListenersAnnot->value as $item) {
                $listenerClassName = $metadata->fullyQualifiedClassName($item);

                if ( ! class_exists($listenerClassName)) {
                    throw Mapping\MappingException::entityListenerClassNotFound($listenerClassName, $className);
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

            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflProperty);

            foreach ($propertyAnnotations as $key => $annot) {
                if (!is_numeric($key)) {
                    continue;
                }

                $propertyAnnotations[get_class($annot)] = $annot;
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany, @Embedded
            switch (true) {
                case isset($propertyAnnotations[Annotation\Column::class]):
                    // Field found
                    $fieldMetadata = $this->convertReflectionPropertyToFieldMetadata(
                        $reflProperty,
                        $propertyAnnotations,
                        $metadata
                    );

                    $metadata->addProperty($fieldMetadata);

                    // Check for Version
                    if ($fieldMetadata instanceof Mapping\VersionFieldMetadata) {
                        $metadata->setVersionProperty($fieldMetadata);
                    }

                    break;

                case isset($propertyAnnotations[Annotation\OneToOne::class]):
                    $assocMetadata = $this->convertReflectionPropertyToOneToOneAssociationMetadata(
                        $reflProperty,
                        $propertyAnnotations,
                        $metadata
                    );

                    $metadata->addProperty($assocMetadata);

                    break;

                case isset($propertyAnnotations[Annotation\ManyToOne::class]):
                    $assocMetadata = $this->convertReflectionPropertyToManyToOneAssociationMetadata(
                        $reflProperty,
                        $propertyAnnotations,
                        $metadata
                    );

                    $metadata->addProperty($assocMetadata);

                    break;

                case isset($propertyAnnotations[Annotation\OneToMany::class]):
                    $assocMetadata = $this->convertReflectionPropertyToOneToManyAssociationMetadata(
                        $reflProperty,
                        $propertyAnnotations,
                        $metadata
                    );

                    $metadata->addProperty($assocMetadata);

                    break;

                case isset($propertyAnnotations[Annotation\ManyToMany::class]):
                    $assocMetadata = $this->convertReflectionPropertyToManyToManyAssociationMetadata(
                        $reflProperty,
                        $propertyAnnotations,
                        $metadata
                    );

                    $metadata->addProperty($assocMetadata);

                    break;

                case isset($propertyAnnotations[Annotation\Embedded::class]):
                    $embeddedAnnot = $propertyAnnotations[Annotation\Embedded::class];

                    $mapping['fieldName']    = $reflProperty->getName();
                    $mapping['class']        = $embeddedAnnot->class;
                    $mapping['columnPrefix'] = $embeddedAnnot->columnPrefix;

                    $metadata->mapEmbedded($mapping);

                    break;
            }
        }

        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations[Annotation\AssociationOverrides::class])) {
            $associationOverridesAnnot = $classAnnotations[Annotation\AssociationOverrides::class];

            foreach ($associationOverridesAnnot->value as $associationOverride) {
                $fieldName = $associationOverride->name;
                $property  = $metadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($metadata->name, $fieldName);
                }

                $existingClass = get_class($property);
                $override      = new $existingClass($fieldName);

                // Check for JoinColumn/JoinColumns annotations
                if ($associationOverride->joinColumns) {
                    $joinColumns = [];

                    foreach ($associationOverride->joinColumns as $joinColumnAnnot) {
                        $joinColumns[] = $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot);
                    }

                    $override->setJoinColumns($joinColumns);
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTableAnnot    = $associationOverride->joinTable;
                    $joinTableMetadata = $this->convertJoinTableAnnotationToJoinTableMetadata($joinTableAnnot);

                    $override->setJoinTable($joinTableMetadata);
                }

                // Check for inversedBy
                if ($associationOverride->inversedBy) {
                    $override->setInversedBy($associationOverride->inversedBy);
                }

                // Check for `fetch`
                if ($associationOverride->fetch) {
                    $override->setFetchMode(
                        constant(Mapping\ClassMetadata::class . '::FETCH_' . $associationOverride->fetch)
                    );
                }

                $metadata->setAssociationOverride($override);
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
     * @throws Mapping\MappingException If the fetch mode is not valid.
     */
    private function getFetchMode($className, $fetchMode)
    {
        $fetchModeConstant = sprintf('%s::%s', Mapping\FetchMode::class, $fetchMode);

        if ( ! defined($fetchModeConstant)) {
            throw Mapping\MappingException::invalidFetchMode($className, $fetchMode);
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

    private function convertReflectionPropertyToFieldMetadata(
        \ReflectionProperty $reflProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $metadata /* @todo Should be removed once FieldMetadata owns Generator information */
    )
    {
        $className   = $reflProperty->getDeclaringClass()->getName();
        $fieldName   = $reflProperty->getName();
        $isVersioned = isset($propertyAnnotations[Annotation\Version::class]);
        $columnAnnot = $propertyAnnotations[Annotation\Column::class];

        if ($columnAnnot->type == null) {
            throw Mapping\MappingException::propertyTypeIsRequired($className, $fieldName);
        }

        $fieldMetadata = $this->convertColumnAnnotationToFieldMetadata($columnAnnot, $fieldName, $isVersioned);

        // Check for Id
        if (isset($propertyAnnotations[Annotation\Id::class])) {
            $fieldMetadata->setPrimaryKey(true);
        }

        // Check for GeneratedValue strategy
        if (isset($propertyAnnotations[Annotation\GeneratedValue::class])) {
            $generatedValueAnnot = $propertyAnnotations[Annotation\GeneratedValue::class];
            $strategy            = strtoupper($generatedValueAnnot->strategy);
            $idGeneratorType     = constant(sprintf('%s::%s', Mapping\GeneratorType::class, $strategy));

            $metadata->setIdGeneratorType($idGeneratorType);
        }

        // Check for CustomGenerator/SequenceGenerator/TableGenerator definition
        switch (true) {
            case isset($propertyAnnotations[Annotation\SequenceGenerator::class]):
                $seqGeneratorAnnot = $propertyAnnotations[Annotation\SequenceGenerator::class];

                $metadata->setGeneratorDefinition([
                    'sequenceName'   => $seqGeneratorAnnot->sequenceName,
                    'allocationSize' => $seqGeneratorAnnot->allocationSize,
                ]);

                break;

            case isset($propertyAnnotations[Annotation\CustomIdGenerator::class]):
                $customGeneratorAnnot = $propertyAnnotations[Annotation\CustomIdGenerator::class];

                $metadata->setGeneratorDefinition([
                    'class'     => $customGeneratorAnnot->class,
                    'arguments' => $customGeneratorAnnot->arguments,
                ]);

                break;

            /* @todo If it is not supported, why does this exist? */
            case isset($propertyAnnotations['Doctrine\ORM\Mapping\TableGenerator']):
                throw Mapping\MappingException::tableIdGeneratorNotImplemented($className);
        }

        return $fieldMetadata;
    }

    private function convertReflectionPropertyToOneToOneAssociationMetadata(
        \ReflectionProperty $reflProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $metadata
    )
    {
        $className     = $reflProperty->getDeclaringClass()->getName();
        $fieldName     = $reflProperty->getName();
        $oneToOneAnnot = $propertyAnnotations[Annotation\OneToOne::class];
        $assocMetadata = new Mapping\OneToOneAssociationMetadata($fieldName);

        $assocMetadata->setTargetEntity($oneToOneAnnot->targetEntity);
        $assocMetadata->setCascade($oneToOneAnnot->cascade);
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
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $metadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        // Check for JoinColumn/JoinColumns annotations
        switch (true) {
            case isset($propertyAnnotations[Annotation\JoinColumn::class]):
                $joinColumnAnnot = $propertyAnnotations[Annotation\JoinColumn::class];

                $assocMetadata->addJoinColumn(
                    $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot)
                );

                break;

            case isset($propertyAnnotations[Annotation\JoinColumns::class]):
                $joinColumnsAnnot = $propertyAnnotations[Annotation\JoinColumns::class];

                foreach ($joinColumnsAnnot->value as $joinColumnAnnot) {
                    $assocMetadata->addJoinColumn(
                        $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot)
                    );
                }

                break;
        }

        return $assocMetadata;
    }

    private function convertReflectionPropertyToManyToOneAssociationMetadata(
        \ReflectionProperty $reflProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $metadata
    )
    {
        $className      = $reflProperty->getDeclaringClass()->getName();
        $fieldName      = $reflProperty->getName();
        $manyToOneAnnot = $propertyAnnotations[Annotation\ManyToOne::class];
        $assocMetadata  = new Mapping\ManyToOneAssociationMetadata($fieldName);

        $assocMetadata->setTargetEntity($manyToOneAnnot->targetEntity);
        $assocMetadata->setCascade($manyToOneAnnot->cascade);
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
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $metadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        // Check for JoinColumn/JoinColumns annotations
        switch (true) {
            case isset($propertyAnnotations[Annotation\JoinColumn::class]):
                $joinColumnAnnot = $propertyAnnotations[Annotation\JoinColumn::class];

                $assocMetadata->addJoinColumn(
                    $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot)
                );

                break;

            case isset($propertyAnnotations[Annotation\JoinColumns::class]):
                $joinColumnsAnnot = $propertyAnnotations[Annotation\JoinColumns::class];

                foreach ($joinColumnsAnnot->value as $joinColumnAnnot) {
                    $assocMetadata->addJoinColumn(
                        $this->convertJoinColumnAnnotationToJoinColumnMetadata($joinColumnAnnot)
                    );
                }

                break;
        }

        return $assocMetadata;
    }

    private function convertReflectionPropertyToOneToManyAssociationMetadata(
        \ReflectionProperty $reflProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $metadata
    )
    {
        $className      = $reflProperty->getDeclaringClass()->getName();
        $fieldName      = $reflProperty->getName();
        $oneToManyAnnot = $propertyAnnotations[Annotation\OneToMany::class];
        $assocMetadata  = new Mapping\OneToManyAssociationMetadata($fieldName);

        $assocMetadata->setTargetEntity($oneToManyAnnot->targetEntity);
        $assocMetadata->setCascade($oneToManyAnnot->cascade);
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
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $metadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        return $assocMetadata;
    }

    private function convertReflectionPropertyToManyToManyAssociationMetadata(
        \ReflectionProperty $reflProperty,
        array $propertyAnnotations,
        Mapping\ClassMetadata $metadata
    )
    {
        $className       = $reflProperty->getDeclaringClass()->getName();
        $fieldName       = $reflProperty->getName();
        $manyToManyAnnot = $propertyAnnotations[Annotation\ManyToMany::class];
        $assocMetadata   = new Mapping\ManyToManyAssociationMetadata($fieldName);

        $assocMetadata->setTargetEntity($manyToManyAnnot->targetEntity);
        $assocMetadata->setCascade($manyToManyAnnot->cascade);
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
            $joinTableMetadata = $this->convertJoinTableAnnotationToJoinTableMetadata($joinTableAnnot);

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
            $cacheMetadata = $this->convertCacheAnnotationToCacheMetadata($cacheAnnot, $metadata, $fieldName);

            $assocMetadata->setCache($cacheMetadata);
        }

        return $assocMetadata;
    }

    /**
     * Parse the given Column as FieldMetadata
     *
     * @param Annotation\Column $columnAnnot
     * @param string            $fieldName
     * @param bool              $isVersioned
     *
     * @return Mapping\FieldMetadata
     */
    private function convertColumnAnnotationToFieldMetadata(Annotation\Column $columnAnnot, string $fieldName, bool $isVersioned)
    {
        $fieldMetadata = $isVersioned
            ? new Mapping\VersionFieldMetadata($fieldName)
            : new Mapping\FieldMetadata($fieldName)
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
     * Parse the given JoinTable as JoinTableMetadata
     *
     * @param Annotation\JoinTable $joinTableAnnot
     *
     * @return Mapping\JoinTableMetadata
     */
    private function convertJoinTableAnnotationToJoinTableMetadata(Annotation\JoinTable $joinTableAnnot)
    {
        $joinTable = new Mapping\JoinTableMetadata();

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

        return $joinTable;
    }

    /**
     * Parse the given JoinColumn as JoinColumnMetadata
     *
     * @param Annotation\JoinColumn $joinColumnAnnot
     *
     * @return Mapping\JoinColumnMetadata
     */
    private function convertJoinColumnAnnotationToJoinColumnMetadata(Annotation\JoinColumn $joinColumnAnnot)
    {
        $joinColumn = new Mapping\JoinColumnMetadata();

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
     * @param Annotation\Cache      $cacheAnnot
     * @param Mapping\ClassMetadata $metadata
     * @param null|string           $fieldName
     *
     * @return Mapping\CacheMetadata
     */
    private function convertCacheAnnotationToCacheMetadata(
        Annotation\Cache $cacheAnnot,
        Mapping\ClassMetadata $metadata,
        $fieldName = null
    )
    {
        $baseRegion    = strtolower(str_replace('\\', '_', $metadata->rootEntityName));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');
        $cacheBuilder  = new CacheMetadataBuilder();

        $cacheBuilder
            ->withUsage(constant(sprintf('%s::%s', Mapping\CacheUsage::class, $cacheAnnot->usage)))
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
