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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver,
    Doctrine\ORM\Mapping\MappingException;

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @since 2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class AnnotationDriver extends AbstractAnnotationDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    protected $entityAnnotationClasses = array(
        'Doctrine\ORM\Mapping\Entity' => 1,
        'Doctrine\ORM\Mapping\MappedSuperclass' => 2,
    );

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass();
        if (!$class) {
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($metadata->name);
        }

        $classAnnotations = $this->reader->getClassAnnotations($class);

        // Compatibility with Doctrine Common 3.x
        if ($classAnnotations && is_int(key($classAnnotations))) {
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
                    $primaryTable['indexes'][$indexAnnot->name] = array(
                        'columns' => $indexAnnot->columns
                    );
                }
            }

            if ($tableAnnot->uniqueConstraints !== null) {
                foreach ($tableAnnot->uniqueConstraints as $uniqueConstraint) {
                    $primaryTable['uniqueConstraints'][$uniqueConstraint->name] = array(
                        'columns' => $uniqueConstraint->columns
                    );
                }
            }

            $metadata->setPrimaryTable($primaryTable);
        }

        // Evaluate NamedQueries annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\NamedQueries'])) {
            $namedQueriesAnnot = $classAnnotations['Doctrine\ORM\Mapping\NamedQueries'];

            if (!is_array($namedQueriesAnnot->value)) {
                throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
            }

            foreach ($namedQueriesAnnot->value as $namedQuery) {
                if (!($namedQuery instanceof \Doctrine\ORM\Mapping\NamedQuery)) {
                    throw new \UnexpectedValueException("@NamedQueries should contain an array of @NamedQuery annotations.");
                }
                $metadata->addNamedQuery(array(
                    'name'  => $namedQuery->name,
                    'query' => $namedQuery->query
                ));
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
                        'length' => $discrColumnAnnot->length
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

            if ($joinColumnAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn')) {
                $joinColumns[] = array(
                    'name' => $joinColumnAnnot->name,
                    'referencedColumnName' => $joinColumnAnnot->referencedColumnName,
                    'unique' => $joinColumnAnnot->unique,
                    'nullable' => $joinColumnAnnot->nullable,
                    'onDelete' => $joinColumnAnnot->onDelete,
                    'columnDefinition' => $joinColumnAnnot->columnDefinition,
                );
            } else if ($joinColumnsAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
                foreach ($joinColumnsAnnot->value as $joinColumn) {
                    $joinColumns[] = array(
                        'name' => $joinColumn->name,
                        'referencedColumnName' => $joinColumn->referencedColumnName,
                        'unique' => $joinColumn->unique,
                        'nullable' => $joinColumn->nullable,
                        'onDelete' => $joinColumn->onDelete,
                        'columnDefinition' => $joinColumn->columnDefinition,
                    );
                }
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            if ($columnAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
                if ($columnAnnot->type == null) {
                    throw MappingException::propertyTypeIsRequired($className, $property->getName());
                }

                $mapping['type'] = $columnAnnot->type;
                $mapping['length'] = $columnAnnot->length;
                $mapping['precision'] = $columnAnnot->precision;
                $mapping['scale'] = $columnAnnot->scale;
                $mapping['nullable'] = $columnAnnot->nullable;
                $mapping['unique'] = $columnAnnot->unique;
                if ($columnAnnot->options) {
                    $mapping['options'] = $columnAnnot->options;
                }

                if (isset($columnAnnot->name)) {
                    $mapping['columnName'] = $columnAnnot->name;
                }

                if (isset($columnAnnot->columnDefinition)) {
                    $mapping['columnDefinition'] = $columnAnnot->columnDefinition;
                }

                if ($idAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
                    $mapping['id'] = true;
                }

                if ($generatedValueAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\GeneratedValue')) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
                }

                if ($versionAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Version')) {
                    $metadata->setVersionMapping($mapping);
                }

                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\SequenceGenerator')) {
                    $metadata->setSequenceGeneratorDefinition(array(
                        'sequenceName' => $seqGeneratorAnnot->sequenceName,
                        'allocationSize' => $seqGeneratorAnnot->allocationSize,
                        'initialValue' => $seqGeneratorAnnot->initialValue
                    ));
                } else if ($tblGeneratorAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                }
            } else if ($oneToOneAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
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
            } else if ($oneToManyAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $mapping['indexBy'] = $oneToManyAnnot->indexBy;
                $mapping['orphanRemoval'] = $oneToManyAnnot->orphanRemoval;
                $mapping['fetch'] = $this->getFetchMode($className, $oneToManyAnnot->fetch);

                if ($orderByAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) {
                if ($idAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
                    $mapping['id'] = true;
                }

                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['inversedBy'] = $manyToOneAnnot->inversedBy;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToOneAnnot->fetch);
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany')) {
                $joinTable = array();

                if ($joinTableAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable')) {
                    $joinTable = array(
                        'name' => $joinTableAnnot->name,
                        'schema' => $joinTableAnnot->schema
                    );

                    foreach ($joinTableAnnot->joinColumns as $joinColumn) {
                        $joinTable['joinColumns'][] = array(
                            'name' => $joinColumn->name,
                            'referencedColumnName' => $joinColumn->referencedColumnName,
                            'unique' => $joinColumn->unique,
                            'nullable' => $joinColumn->nullable,
                            'onDelete' => $joinColumn->onDelete,
                            'columnDefinition' => $joinColumn->columnDefinition,
                        );
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = array(
                            'name' => $joinColumn->name,
                            'referencedColumnName' => $joinColumn->referencedColumnName,
                            'unique' => $joinColumn->unique,
                            'nullable' => $joinColumn->nullable,
                            'onDelete' => $joinColumn->onDelete,
                            'columnDefinition' => $joinColumn->columnDefinition,
                        );
                    }
                }

                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $mapping['inversedBy'] = $manyToManyAnnot->inversedBy;
                $mapping['cascade'] = $manyToManyAnnot->cascade;
                $mapping['indexBy'] = $manyToManyAnnot->indexBy;
                $mapping['fetch'] = $this->getFetchMode($className, $manyToManyAnnot->fetch);

                if ($orderByAnnot = $this->reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OrderBy')) {
                    $mapping['orderBy'] = $orderByAnnot->value;
                }

                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate @HasLifecycleCallbacks annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\HasLifecycleCallbacks'])) {
            foreach ($class->getMethods() as $method) {
                // filter for the declaring class only, callbacks from parents will already be registered.
                if ($method->isPublic() && $method->getDeclaringClass()->getName() == $class->name) {
                    $annotations = $this->reader->getMethodAnnotations($method);

                    // Compatibility with Doctrine Common 3.x
                    if ($annotations && is_int(key($annotations))) {
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
        if(!defined('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode)) {
            throw MappingException::invalidFetchMode($className,  $fetchMode);
        }

        return constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . $fetchMode);
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
