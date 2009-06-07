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

use Doctrine\Common\DoctrineException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;

/* Addendum annotation reflection extensions */
if ( ! class_exists('Addendum', false)) {
    require __DIR__ . '/../../../../vendor/addendum/annotations.php';
}
require __DIR__ . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations
 * with the help of the Addendum reflection extensions.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class AnnotationDriver implements Driver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $annotClass = new \ReflectionAnnotatedClass($className);

        // Evaluate DoctrineEntity annotation
        if (($entityAnnot = $annotClass->getAnnotation('Entity')) === false) {
            throw DoctrineException::updateMe("$className is no entity.");
        }
        $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);

        // Evaluate DoctrineTable annotation
        if ($tableAnnot = $annotClass->getAnnotation('Table')) {
            $metadata->setPrimaryTable(array(
                'name' => $tableAnnot->name,
                'schema' => $tableAnnot->schema
            ));
        }

        // Evaluate InheritanceType annotation
        if ($inheritanceTypeAnnot = $annotClass->getAnnotation('InheritanceType')) {
            $metadata->setInheritanceType(constant('\Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value));
        }

        // Evaluate DiscriminatorColumn annotation
        if ($discrColumnAnnot = $annotClass->getAnnotation('DiscriminatorColumn')) {
            $metadata->setDiscriminatorColumn(array(
                'name' => $discrColumnAnnot->name,
                'type' => $discrColumnAnnot->type,
                'length' => $discrColumnAnnot->length
            ));
        }

        // Evaluate DiscriminatorValue annotation
        if ($discrValueAnnot = $annotClass->getAnnotation('DiscriminatorValue')) {
            $metadata->setDiscriminatorValue($discrValueAnnot->value);
        }

        // Evaluate DoctrineSubClasses annotation
        if ($subClassesAnnot = $annotClass->getAnnotation('SubClasses')) {
            $metadata->setSubclasses($subClassesAnnot->value);
        }

        // Evaluate DoctrineChangeTrackingPolicy annotation
        if ($changeTrackingAnnot = $annotClass->getAnnotation('ChangeTrackingPolicy')) {
            $metadata->setChangeTrackingPolicy($changeTrackingAnnot->value);
        }

        // Evaluate annotations on properties/fields
        foreach ($annotClass->getProperties() as $property) {
            if ($metadata->hasField($property->getName())) {
                continue;
            }

            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            // Check for DoctrineJoinColummn/DoctrineJoinColumns annotations
            $joinColumns = array();
            if ($joinColumnAnnot = $property->getAnnotation('JoinColumn')) {
                $joinColumns[] = array(
                        'name' => $joinColumnAnnot->name,
                        'referencedColumnName' => $joinColumnAnnot->referencedColumnName,
                        'unique' => $joinColumnAnnot->unique,
                        'nullable' => $joinColumnAnnot->nullable,
                        'onDelete' => $joinColumnAnnot->onDelete,
                        'onUpdate' => $joinColumnAnnot->onUpdate
                );
            } else if ($joinColumnsAnnot = $property->getAnnotation('JoinColumns')) {
                $joinColumns = $joinColumnsAnnot->value;
            }

            // Field can only be annotated with one of: DoctrineColumn,
            // DoctrineOneToOne, DoctrineOneToMany, DoctrineManyToOne, DoctrineManyToMany
            if ($columnAnnot = $property->getAnnotation('Column')) {
                if ($columnAnnot->type == null) {
                    throw DoctrineException::updateMe("Missing type on property " . $property->getName());
                }
                $mapping['type'] = $columnAnnot->type;
                $mapping['length'] = $columnAnnot->length;
                $mapping['nullable'] = $columnAnnot->nullable;
                if (isset($columnAnnot->name)) {
                    $mapping['columnName'] = $columnAnnot->name;
                }
                if ($idAnnot = $property->getAnnotation('Id')) {
                    $mapping['id'] = true;
                }
                if ($generatedValueAnnot = $property->getAnnotation('GeneratedValue')) {
                    if ($generatedValueAnnot->strategy == 'auto') {
                        try {
                            throw new \Exception();
                        } catch (\Exception $e) {
                            var_dump($e->getTraceAsString());
                        }
                    }
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_' . $generatedValueAnnot->strategy));
                }
                $metadata->mapField($mapping);

                // Check for SequenceGenerator/TableGenerator definition
                if ($seqGeneratorAnnot = $property->getAnnotation('SequenceGenerator')) {
                    $metadata->setSequenceGeneratorDefinition(array(
                        'sequenceName' => $seqGeneratorAnnot->sequenceName,
                        'allocationSize' => $seqGeneratorAnnot->allocationSize,
                        'initialValue' => $seqGeneratorAnnot->initialValue
                    ));
                } else if ($tblGeneratorAnnot = $property->getAnnotation('TableGenerator')) {
                    throw new DoctrineException("DoctrineTableGenerator not yet implemented.");
                }
                
            } else if ($oneToOneAnnot = $property->getAnnotation('OneToOne')) {
                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAnnot = $property->getAnnotation('OneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $property->getAnnotation('ManyToOne')) {
                $mapping['joinColumns'] = $joinColumns;
                $mapping['cascade'] = $manyToOneAnnot->cascade;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $property->getAnnotation('ManyToMany')) {
                $joinTable = array();
                if ($joinTableAnnot = $property->getAnnotation('JoinTable')) {
                    $joinTable = array(
                        'name' => $joinTableAnnot->name,
                        'schema' => $joinTableAnnot->schema,
                        'joinColumns' => $joinTableAnnot->joinColumns,
                        'inverseJoinColumns' => $joinTableAnnot->inverseJoinColumns
                    );
                }
                $mapping['joinTable'] = $joinTable;
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $mapping['cascade'] = $manyToManyAnnot->cascade;
                $metadata->mapManyToMany($mapping);
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
        $refClass = new \ReflectionClass($className);
        $docComment = $refClass->getDocComment();
        return strpos($docComment, '@Entity') === false &&
                strpos($docComment, '@MappedSuperclass') === false;
    }
    
    public function preload()
    {
        return array();
    }
}