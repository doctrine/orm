<?php

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Exceptions\MappingException;

/* Addendum annotation reflection extensions */
if ( ! class_exists('\Addendum', false)) {
    require dirname(__FILE__) . '/addendum/annotations.php';
}
require dirname(__FILE__) . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @author robo
 */
class AnnotationDriver
{
    /**
     * Loads the metadata for the specified class into the provided container.
     */
    public function loadMetadataForClass($className, \Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $annotClass = new \Addendum\ReflectionAnnotatedClass($className);

        if (($entityAnnot = $annotClass->getAnnotation('DoctrineEntity')) === false) {
            throw new MappingException("$className is no entity.");
        }

        if ($entityAnnot->tableName) {
            $metadata->setTableName($entityAnnot->tableName);
        }
        $metadata->setCustomRepositoryClass($entityAnnot->repositoryClass);

        if ($inheritanceTypeAnnot = $annotClass->getAnnotation('DoctrineInheritanceType')) {
            $metadata->setInheritanceType($inheritanceTypeAnnot->value);
        }

        if ($discrColumnAnnot = $annotClass->getAnnotation('DoctrineDiscriminatorColumn')) {
            $metadata->setDiscriminatorColumn(array(
                'name' => $discrColumnAnnot->name,
                'type' => $discrColumnAnnot->type,
                'length' => $discrColumnAnnot->length
            ));
        }

        if ($discrValueAnnot = $annotClass->getAnnotation('DoctrineDiscriminatorMap')) {
            $metadata->setDiscriminatorMap((array)$discrValueAnnot->value);
        }
        
        if ($subClassesAnnot = $annotClass->getAnnotation('DoctrineSubClasses')) {
            $metadata->setSubclasses($subClassesAnnot->value);
        }

        foreach ($annotClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();
            if ($columnAnnot = $property->getAnnotation('DoctrineColumn')) {
                if ($columnAnnot->type == null) {
                    throw new MappingException("Missing type on property " . $property->getName());
                }
                $mapping['type'] = $columnAnnot->type;
                $mapping['length'] = $columnAnnot->length;
                if ($idAnnot = $property->getAnnotation('DoctrineId')) {
                    $mapping['id'] = true;
                }
                if ($idGeneratorAnnot = $property->getAnnotation('DoctrineIdGenerator')) {
                    $mapping['idGenerator'] = $idGeneratorAnnot->value;
                }
                $metadata->mapField($mapping);
            } else if ($oneToOneAnnot = $property->getAnnotation('DoctrineOneToOne')) {
                $mapping['targetEntity'] = $oneToOneAnnot->targetEntity;
                $mapping['joinColumns'] = $oneToOneAnnot->joinColumns;
                $mapping['mappedBy'] = $oneToOneAnnot->mappedBy;
                $mapping['cascade'] = $oneToOneAnnot->cascade;
                $metadata->mapOneToOne($mapping);
            } else if ($oneToManyAnnot = $property->getAnnotation('DoctrineOneToMany')) {
                $mapping['mappedBy'] = $oneToManyAnnot->mappedBy;
                $mapping['targetEntity'] = $oneToManyAnnot->targetEntity;
                $mapping['cascade'] = $oneToManyAnnot->cascade;
                $metadata->mapOneToMany($mapping);
            } else if ($manyToOneAnnot = $property->getAnnotation('DoctrineManyToOne')) {
                $mapping['joinColumns'] = $manyToOneAnnot->joinColumns;
                $mapping['targetEntity'] = $manyToOneAnnot->targetEntity;
                $metadata->mapManyToOne($mapping);
            } else if ($manyToManyAnnot = $property->getAnnotation('DoctrineManyToMany')) {
                $mapping['targetEntity'] = $manyToManyAnnot->targetEntity;
                $mapping['joinColumns'] = $manyToManyAnnot->joinColumns;
                $mapping['inverseJoinColumns'] = $manyToManyAnnot->inverseJoinColumns;
                $mapping['joinTable'] = $manyToManyAnnot->joinTable;
                $mapping['mappedBy'] = $manyToManyAnnot->mappedBy;
                $metadata->mapManyToMany($mapping);
            }
        }
    }
}
