<?php
/*
 *  $Id$
 *
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

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\MappingException;

require __DIR__ . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AnnotationDriver implements Driver
{
    /** The AnnotationReader. */
    private $_reader;
    
    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     * 
     * @param AnnotationReader $reader The AnnotationReader to use.
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->_reader = $reader;
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass();
        
        $classAnnotations = $this->_reader->getClassAnnotations($class);

        // Evaluate Entity annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\Entity'])) {
            $entityAnnot = $classAnnotations['Doctrine\ORM\Mapping\Entity'];
            $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);
        } else if (isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass'])) {
            $metadata->isMappedSuperclass = true;
        } else {
            throw DoctrineException::classIsNotAValidEntityOrMapperSuperClass($className);
        }

        // Evaluate DoctrineTable annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\Table'])) {
            $tableAnnot = $classAnnotations['Doctrine\ORM\Mapping\Table'];
            $primaryTable = array(
                'name' => $tableAnnot->name,
                'schema' => $tableAnnot->schema
            );
            
            if ($tableAnnot->indexes !== null) {
                foreach ($tableAnnot->indexes as $indexAnnot) {
                    $primaryTable['indexes'][$indexAnnot->name] = array('fields' => $indexAnnot->columns);
                }
            }
            
            if ($tableAnnot->uniqueConstraints !== null) {
                foreach ($tableAnnot->uniqueConstraints as $uniqueConstraint) {
                    $primaryTable['uniqueConstraints'][] = $uniqueConstraint->columns;
                }
            }
            
            $metadata->setPrimaryTable($primaryTable);
        }

        // Evaluate InheritanceType annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\InheritanceType'])) {
            $inheritanceTypeAnnot = $classAnnotations['Doctrine\ORM\Mapping\InheritanceType'];
            $metadata->setInheritanceType(constant('\Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value));
        }

        // Evaluate DiscriminatorColumn annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'])) {
            $discrColumnAnnot = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorColumn'];
            $metadata->setDiscriminatorColumn(array(
                'name' => $discrColumnAnnot->name,
                'type' => $discrColumnAnnot->type,
                'length' => $discrColumnAnnot->length
            ));
        }

        // Evaluate DiscriminatorMap annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'])) {
            $discrMapAnnot = $classAnnotations['Doctrine\ORM\Mapping\DiscriminatorMap'];
            $metadata->setDiscriminatorMap($discrMapAnnot->value);
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'])) {
            $changeTrackingAnnot = $classAnnotations['Doctrine\ORM\Mapping\ChangeTrackingPolicy'];
            $metadata->setChangeTrackingPolicy($changeTrackingAnnot->value);
        }

        // Evaluate annotations on properties/fields
        foreach ($class->getProperties() as $property) {
            if ($metadata->hasField($property->getName())) {
                continue;
            }

            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            // Check for JoinColummn/JoinColumns annotations
            $joinColumns = array();
            
            if ($joinColumnAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumn')) {
                $joinColumns[] = array(
                    'name' => $joinColumnAnnot->name,
                    'referencedColumnName' => $joinColumnAnnot->referencedColumnName,
                    'unique' => $joinColumnAnnot->unique,
                    'nullable' => $joinColumnAnnot->nullable,
                    'onDelete' => $joinColumnAnnot->onDelete,
                    'onUpdate' => $joinColumnAnnot->onUpdate
                );
            } else if ($joinColumnsAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinColumns')) {
                foreach ($joinColumnsAnnot->value as $joinColumn) {
                    $joinColumns[] = array(
                        'name' => $joinColumn->name,
                        'referencedColumnName' => $joinColumn->referencedColumnName,
                        'unique' => $joinColumn->unique,
                        'nullable' => $joinColumn->nullable,
                        'onDelete' => $joinColumn->onDelete,
                        'onUpdate' => $joinColumn->onUpdate
                    );
                }
            }

            // Field can only be annotated with one of:
            // @Column, @OneToOne, @OneToMany, @ManyToOne, @ManyToMany
            if ($columnAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column')) {
                if ($columnAnnot->type == null) {
                    throw DoctrineException::propertyTypeIsRequired($property->getName());
                }
                
                $mapping['type'] = $columnAnnot->type;
                $mapping['length'] = $columnAnnot->length;
                $mapping['precision'] = $columnAnnot->precision;
                $mapping['scale'] = $columnAnnot->scale;
                $mapping['nullable'] = $columnAnnot->nullable;
                $mapping['options'] = $columnAnnot->options;
                $mapping['unique'] = $columnAnnot->unique;
                
                if (isset($columnAnnot->default)) {
                    $mapping['default'] = $columnAnnot->default;
                }
                
                if (isset($columnAnnot->name)) {
                    $mapping['columnName'] = $columnAnnot->name;
                }
                
                if ($idAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) {
                    $mapping['id'] = true;
                }
                
                if ($generatedValueAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\GeneratedValue')) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
                }
                
                if ($versionAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Version')) {
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
                } else if ($tblGeneratorAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\TableGenerator')) {
                    throw DoctrineException::tableIdGeneratorNotImplemented();
                }
            } else if ($oneToOneAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {
                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $mapping['orphanRemoval'] = $oneToOneAnnot->orphanRemoval;
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $mapping['orphanRemoval'] = $oneToManyAnnot->orphanRemoval;
                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) {
                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany')) {
                $joinTable = array();
                
                if ($joinTableAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable')) {
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
                            'onUpdate' => $joinColumn->onUpdate
                        );
                    }

                    foreach ($joinTableAnnot->inverseJoinColumns as $joinColumn) {
                        $joinTable['inverseJoinColumns'][] = array(
                            'name' => $joinColumn->name,
                            'referencedColumnName' => $joinColumn->referencedColumnName,
                            'unique' => $joinColumn->unique,
                            'nullable' => $joinColumn->nullable,
                            'onDelete' => $joinColumn->onDelete,
                            'onUpdate' => $joinColumn->onUpdate
                        );
                    }
                }
                
                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $mapping['cascade'] = $manyToManyAnnot->cascade;
                $metadata->mapManyToMany($mapping);
            }   
        }
        
        // Evaluate HasLifecycleCallbacks annotation
        if (isset($classAnnotations['Doctrine\ORM\Mapping\HasLifecycleCallbacks'])) {
            foreach ($class->getMethods() as $method) {
                if ($method->isPublic()) {
                    $annotations = $this->_reader->getMethodAnnotations($method);
                    
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
                }
            }
        }
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is annotated with either @Entity or
     * @MappedSuperclass in the class doc block.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $classAnnotations = $this->_reader->getClassAnnotations(new \ReflectionClass($className));
        
        return ! isset($classAnnotations['Doctrine\ORM\Mapping\Entity']) &&
               ! isset($classAnnotations['Doctrine\ORM\Mapping\MappedSuperclass']);
    }
    
    public function preload()
    {
        return array();
    }
}