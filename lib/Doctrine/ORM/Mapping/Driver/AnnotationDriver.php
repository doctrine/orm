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

use Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException,
    Doctrine\ORM\Mapping\JoinColumn,
    Doctrine\ORM\Mapping\Column;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @since 2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class AnnotationDriver implements Driver
{
    /**
     * The AnnotationReader.
     *
     * @var AnnotationReader
     */
    protected $_reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    protected $_paths = array();

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $_fileExtension = '.php';

    /**
     * @param array
     */
    protected $_classNames;

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     *
     * @param AnnotationReader $reader The AnnotationReader to use, duck-typed.
     * @param string|array $paths One or multiple paths where mapping classes can be found.
     */
    public function __construct($reader, $paths = null)
    {
        $this->_reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    /**
     * Append lookup paths to metadata driver.
     *
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Retrieve the current annotation reader
     *
     * @return AnnotationReader
     */
    public function getReader()
    {
        return $this->_reader;
    }

    /**
     * Get the file extension used to look for mapping files under
     *
     * @return void
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Set the file extension used to look for mapping files under
     *
     * @param string $fileExtension The file extension to set
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $class = $metadata->getReflectionClass();
        if ( ! $class) {
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($metadata->name);
        }

        $classAnnotations = $this->_reader->getClassAnnotations($class);

        if ($classAnnotations && is_numeric(key($classAnnotations))) {
            foreach ($classAnnotations as $annot) {
                $classAnnotations[get_class($annot)] = $annot;
            }
        }

        // Evaluate Entity annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\Entity'])) {
            $entityAnnot = $classAnnotations['Doctrine\ORM\Mapping\Entity'];
            if ($entityAnnot->repositoryClass !== null) {
                $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);
            }
            if ($entityAnnot->readOnly) {
                $metadata->markReadOnly();
            }
        } else if (isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'])) {
            $mappedSuperclassAnnot = $classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'];
            $metadata->setCustomRepositoryClass($mappedSuperclassAnnot->repositoryClass);
            $metadata->isMappedSuperclass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate Table annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\Table'])) {
            $tableAnnot = $classAnnotations['Doctrine\ORM\Mapping\Table'];
            $primaryTable = array(
                'name' => $tableAnnot->name,
                'schema' => $tableAnnot->schema
            );

            if ($tableAnnot->indexes !== null) {
                foreach ($tableAnnot->indexes as $indexAnnot) {
                    $index = array('columns' => $indexAnnot->columns);

                    if ( ! empty($indexAnnot->name)) {
                        $primaryTable['indexes'][$indexAnnot->name] = $index;
                    } else {
                        $primaryTable['indexes'][] = $index;
                    }
                }
            }

            if ($tableAnnot->uniqueConstraints !== null) {
                foreach ($tableAnnot->uniqueConstraints as $uniqueConstraintAnnot) {
                    $uniqueConstraint = array('columns' => $uniqueConstraintAnnot->columns);

                    if ( ! empty($uniqueConstraintAnnot->name)) {
                        $primaryTable['uniqueConstraints'][$uniqueConstraintAnnot->name] = $uniqueConstraint;
                    } else {
                        $primaryTable['uniqueConstraints'][] = $uniqueConstraint;
                    }
                }
            }

            if ($tableAnnot->options !== null) {
                $primaryTable['options'] = $tableAnnot->options;
            }

            $metadata->setPrimaryTable($primaryTable);
        }

        // Evaluate NamedNativeQueries annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedNativeQueries'])) {
            $namedNativeQueriesAnnot = $classAnnotations['Doctrine\ORM\Mapping\NamedNativeQueries'];

            foreach ($namedNativeQueriesAnnot->value as $namedNativeQuery) {
                $metadata->addNamedNativeQuery(array(
                    'name'              => $namedNativeQuery->name,
                    'query'             => $namedNativeQuery->query,
                    'resultClass'       => $namedNativeQuery->resultClass,
                    'resultSetMapping'  => $namedNativeQuery->resultSetMapping,
                ));
            }
        }

        // Evaluate SqlResultSetMappings annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\SqlResultSetMappings'])) {
            $sqlResultSetMappingsAnnot = $classAnnotations['Doctrine\ORM\Mapping\SqlResultSetMappings'];

            foreach ($sqlResultSetMappingsAnnot->value as $resultSetMapping) {
                $entities = array();
                $columns  = array();
                foreach ($resultSetMapping->entities as $entityResultAnnot) {
                    $entityResult = array(
                        'fields'                => array(),
                        'entityClass'           => $entityResultAnnot->entityClass,
                        'discriminatorColumn'   => $entityResultAnnot->discriminatorColumn,
                    );

                    foreach ($entityResultAnnot->fields as $fieldResultAnnot) {
                        $entityResult['fields'][] = array(
                            'name'      => $fieldResultAnnot->name,
                            'column'    => $fieldResultAnnot->column
                        );
                    }

                    $entities[] = $entityResult;
                }

                foreach ($resultSetMapping->columns as $columnResultAnnot) {
                    $columns[] = array(
                        'name' => $columnResultAnnot->name,
                    );
                }

                $metadata->addSqlResultSetMapping(array(
                    'name'          => $resultSetMapping->name,
                    'entities'      => $entities,
                    'columns'       => $columns
                ));
            }
        }

        // Evaluate NamedQueries annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedQueries'])) {
            $namedQueriesAnnot = $classAnnotations['Doctrine\ORM\Mapping\NamedQueries'];

            if ( ! is_array($namedQueriesAnnot->value)) {
                throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
            }

            foreach ($namedQueriesAnnot->value as $namedQuery) {
                if ( ! ($namedQuery instanceof \Doctrine\ORM\Mapping\NamedQuery)) {
                    throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
                }
                $metadata->addNamedQuery(array(
                    'name'  => $namedQuery->name,
                    'query' => $namedQuery->query
                ));
            }
        }

        $associationOverrides = array();
        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'])) {
            $associationOverridesAnnot = $classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'];

            foreach ($associationOverridesAnnot->value as $associationOverride) {
                // Check for JoinColummn/JoinColumns annotations
                if ($associationOverride->joinColumns) {
                    $joinColumns = array();
                    foreach ($associationOverride->joinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }
                    $associationOverrides[$associationOverride->name]['joinColumns'] = $joinColumns;
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTable      = null;
                    $joinTableAnnot = $associationOverride->joinTable;
                    $joinTable = array(
                        'name'      => $joinTableAnnot->name,
                        'schema'    => $joinTableAnnot->schema
                    );

                    foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    $associationOverrides[$associationOverride->name]['joinTable'] = $joinTable;
                }
            }
        }

        // Evaluate InheritanceType annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\InheritanceType'])) {
            $inheritanceTypeAnnot = $classAnnotations['Doctrine\ORM\Mapping\InheritanceType'];
            $metadata->setInheritanceType(constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value));

            if ($metadata->inheritanceType != \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
                // Evaluate DiscriminatorColumn annotation
                if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'])) {
                    $discrColumnAnnot = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'];
                    $metadata->setDiscriminatorColumn(array(
                        'name' => $discrColumnAnnot->name,
                        'type' => $discrColumnAnnot->type,
                        'length' => $discrColumnAnnot->length,
                        'columnDefinition'    => $discrColumnAnnot->columnDefinition
                    ));
                } else {
                    $metadata->setDiscriminatorColumn(array('name' => 'dtype', 'type' => 'string', 'length' => 255));
                }

                // Evaluate DiscriminatorMap annotation
                if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'])) {
                    $discrMapAnnot = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'];
                    $metadata->setDiscriminatorMap($discrMapAnnot->value);
                }
            }
        }


        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'])) {
            $changeTrackingAnnot = $classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'];
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_' . $changeTrackingAnnot->value));
        }

        // Evaluate annotations on properties/fields
        foreach ($class->getProperties() as $property) {
            if ($metadata->isMappedSuperclass && ! $property->isPrivate()
                ||
                $metadata->isInheritedField($property->name)
                ||
                $metadata->isInheritedAssociation($property->name)) {
                continue;
            }

            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            // Check for JoinColummn/JoinColumns annotations
            $joinColumns = array();

            if ($joinColumnAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn')) {
                $joinColumns[] = $this->joinColumnToArray($joinColumnAnnot);
            } else if ($joinColumnsAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
                foreach ($joinColumnsAnnot->value as $joinColumn) {
                    $joinColumns[] = $this->joinColumnToArray($joinColumn);
                }
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            if ($columnAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
                if ($columnAnnot->type == null) {
                    throw MappingException::propertyTypeIsRequired($className, $property->getName());
                }

                $mapping = $this->columnToArray($property->getName(), $columnAnnot);

                if ($idAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
                    $mapping['id'] = true;
                }

                if ($generatedValueAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\GeneratedValue')) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
                }

                if ($this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Version')) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\SequenceGenerator')) {
                    $metadata->setSequenceGeneratorDefinition(array(
                        'sequenceName' => $seqGeneratorAnnot->sequenceName,
                        'allocationSize' => $seqGeneratorAnnot->allocationSize,
                        'initialValue' => $seqGeneratorAnnot->initialValue
                    ));
                } else if ($this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                } else if ($customGeneratorAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\CustomIdGenerator')) {
                    $metadata->setCustomGeneratorDefinition(array(
                        'class' => $customGeneratorAnnot->class
                    ));
                }
            } else if ($oneToOneAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {
                if ($idAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
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
            } else if ($oneToManyAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $mapping['indexBy'] = $oneToManyAnnot->indexBy;
                $mapping['orphanRemoval'] = $oneToManyAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToManyAnnot->fetch);

                if ($orderByAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) {
                if ($idAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
                    $mapping['id'] = true;
                }

                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['inversedBy'] = $manyToOneAnnot->inversedBy;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnot->fetch);
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany')) {
                $joinTable = array();

                if ($joinTableAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable')) {
                    $joinTable = array(
                        'name' => $joinTableAnnot->name,
                        'schema' => $joinTableAnnot->schema
                    );

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

                if ($orderByAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate AssociationOverrides annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'])) {
            $associationOverridesAnnot = $classAnnotations['Doctrine\ORM\Mapping\AssociationOverrides'];

            foreach ($associationOverridesAnnot->value as $associationOverride) {
                $override   = array();
                $fieldName  = $associationOverride->name;

                // Check for JoinColummn/JoinColumns annotations
                if ($associationOverride->joinColumns) {
                    $joinColumns = array();
                    foreach ($associationOverride->joinColumns as $joinColumn) {
                        $joinColumns[] = $this->joinColumnToArray($joinColumn);
                    }
                    $override['joinColumns'] = $joinColumns;
                }

                // Check for JoinTable annotations
                if ($associationOverride->joinTable) {
                    $joinTable      = null;
                    $joinTableAnnot = $associationOverride->joinTable;
                    $joinTable = array(
                        'name'      => $joinTableAnnot->name,
                        'schema'    => $joinTableAnnot->schema
                    );

                    foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = $this->joinColumnToArray($joinColumn);
                    }

                    $override['joinTable'] = $joinTable;
                }

                $metadata->setAssociationOverride($fieldName, $override);
            }
        }

         $attributeOverrides = array();
        // Evaluate AttributeOverrides annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\AttributeOverrides'])) {
            $attributeOverridesAnnot = $classAnnotations['Doctrine\ORM\Mapping\AttributeOverrides'];
            foreach ($attributeOverridesAnnot->value as $attributeOverrideAnnot) {
                $attributeOverride = $this->columnToArray($attributeOverrideAnnot->name, $attributeOverrideAnnot->column);
                $metadata->setAttributeOverride($attributeOverrideAnnot->name, $attributeOverride);
            }
        }

        foreach ($class->getMethods() as $method) {
            // filter for the declaring class only, callbacks from parents will already be registered.
            if ($method->getDeclaringClass()->getName() == $class->name) {
                $annotations = $this->_reader->getMethodAnnotations($method);

                if ($annotations && is_numeric(key($annotations))) {
                    foreach ($annotations as $annot) {
                        $annotations[get_class($annot)] = $annot;
                    }
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PrePersist'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::prePersist);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PostPersist'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postPersist);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PreUpdate'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preUpdate);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PostUpdate'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postUpdate);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PreRemove'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preRemove);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PostRemove'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postRemove);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PostLoad'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::postLoad);
                }

                if (isset($annotations['Doctrine\ORM\Mapping\PreFlush'])) {
                    $metadata->addLifecycleCallback($method->getName(), \Doctrine\ORM\Events::preFlush);
                }
            }
        }
    }

    /**
     * Whether the class with the specified name is transient. Only non-transient
     * classes, that is entities and mapped superclasses, should have their metadata loaded.
     * A class is non-transient if it is annotated with either @Entity or
     * @MappedSuperclass in the class doc block.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $classAnnotations = $this->_reader->getClassAnnotations(new \ReflectionClass($className));

        if ($classAnnotations && is_numeric(key($classAnnotations))) {
            foreach ($classAnnotations as $annot) {
                if ($annot instanceof \Doctrine\ORM\Mapping\Entity) {
                    return false;
                }
                if ($annot instanceof \Doctrine\ORM\Mapping\MappedSuperclass) {
                    return false;
                }
            }

            return true;
        }

        return ! isset($classAnnotations['Doctrine\ORM\Mapping\Entity']) &&
               ! isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass']);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        if ($this->_classNames !== null) {
            return $this->_classNames;
        }

        if ( ! $this->_paths) {
            throw MappingException::pathRequired();
        }

        $classes = array();
        $includedFiles = array();

        foreach ($this->_paths as $path) {
            if ( ! is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+' . str_replace('.', '\.', $this->_fileExtension) . '$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = realpath($file[0]);

                require_once $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            if (in_array($sourceFile, $includedFiles) && ! $this->isTransient($className)) {
                $classes[] = $className;
            }
        }

        $this->_classNames = $classes;

        return $classes;
    }

    /**
     * Attempts to resolve the fetch mode.
     *
     * @param string $className The class name
     * @param string $fetchMode The fetch mode
     * @return integer The fetch mode as defined in ClassMetadata
     * @throws MappingException If the fetch mode is not valid
     */
    private function getFetchMode($className, $fetchMode)
    {
        if( ! defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
            throw MappingException::invalidFetchMode($className,  $fetchMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode);
    }

    /**
     * Parse the given JoinColumn as array
     *
     * @param   JoinColumn $joinColumn
     * @return  array
     */
    private function joinColumnToArray(JoinColumn $joinColumn)
    {
        return array(
            'name' => $joinColumn->name,
            'unique' => $joinColumn->unique,
            'nullable' => $joinColumn->nullable,
            'onDelete' => $joinColumn->onDelete,
            'columnDefinition' => $joinColumn->columnDefinition,
            'referencedColumnName' => $joinColumn->referencedColumnName,
        );
    }

    /**
     * Parse the given Column as array
     *
     * @param   string $fieldName
     * @param   Column $column
     * @return  array
     */
    private function columnToArray($fieldName, Column $column)
    {
        $mapping = array(
            'fieldName' => $fieldName,
            'type'      => $column->type,
            'scale'     => $column->scale,
            'length'    => $column->length,
            'unique'    => $column->unique,
            'nullable'  => $column->nullable,
            'precision' => $column->precision
        );

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
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param AnnotationReader $reader
     * @return AnnotationDriver
     */
    static public function create($paths = array(), AnnotationReader $reader = null)
    {
        if ($reader == null) {
            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        }
        return new self($reader, $paths);
    }
}
