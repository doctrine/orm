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

namespace Doctrine\ORM\Tools\Export\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\OneToOneMapping,
    Doctrine\ORM\Mapping\OneToManyMapping,
    Doctrine\ORM\Mapping\ManyToManyMapping;

/**
 * ClassMetadata exporter for Doctrine YAML mapping files
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class YamlExporter extends AbstractExporter
{
    protected $_extension = '.dcm.yml';

    /**
     * Converts a single ClassMetadata instance to the exported format
     * and returns it
     *
     * TODO: Should this code be pulled out in to a toArray() method in ClassMetadata
     *
     * @param ClassMetadataInfo $metadata 
     * @return mixed $exported
     */
    public function exportClassMetadata(ClassMetadataInfo $metadata)
    {
        $array = array();
        if ($metadata->isMappedSuperclass) {
            $array['type'] = 'mappedSuperclass';
        } else {
            $array['type'] = 'entity';
        }
        $array['table'] = $metadata->primaryTable['name'];

        if (isset($metadata->primaryTable['schema'])) {
            $array['schema'] = $metadata->primaryTable['schema'];
        }

        $inheritanceType = $metadata->inheritanceType;
        if ($inheritanceType !== ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $array['inheritanceType'] = $this->_getInheritanceTypeString($inheritanceType);
        }

        if ($column = $metadata->discriminatorColumn) {
            $array['discriminatorColumn'] = $column;
        }

        if ($map = $metadata->discriminatorMap) {
            $array['discriminatorMap'] = $map;
        }

        if ($metadata->changeTrackingPolicy !== ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $array['changeTrackingPolicy'] = $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy);
        }

        if (isset($metadata->primaryTable['indexes'])) {
            $array['indexes'] = $metadata->primaryTable['indexes'];
        }

        if (isset($metadata->primaryTable['uniqueConstraints'])) {
            $array['uniqueConstraints'] = $metadata->primaryTable['uniqueConstraints'];
        }
        
        $fieldMappings = $metadata->fieldMappings;
        
        $ids = array();
        foreach ($fieldMappings as $name => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $fieldMapping['type'] = $fieldMapping['type'] . '(' . $fieldMapping['length'] . ')';
                unset($fieldMapping['length']);
            }

            $fieldMapping['column'] = $fieldMapping['columnName'];
            unset(
                $fieldMapping['columnName'],
                $fieldMapping['fieldName']
            );
 
            if ($fieldMapping['column'] == $name) {
                unset($fieldMapping['column']);
            }

            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                $ids[$name] = $fieldMapping;
                unset($fieldMappings[$name]);
                continue;
            }

            $fieldMappings[$name] = $fieldMapping;
        }

        if ($idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $ids[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $this->_getIdGeneratorTypeString($metadata->generatorType);
        }
        
        if ($ids) {
            $array['fields'] = $ids;
        }

        if ($fieldMappings) {
            if ( ! isset($array['fields'])) {
                $array['fields'] = array();
            }
            $array['fields'] = array_merge($array['fields'], $fieldMappings);
        }

        $associations = array();
        foreach ($metadata->associationMappings as $name => $associationMapping) {
            $associationMappingArray = array(
                'targetEntity' => $associationMapping->targetEntityName,
                'cascade'     => array(
                    'remove'  => $associationMapping->isCascadeRemove,
                    'persist' => $associationMapping->isCascadePersist,
                    'refresh' => $associationMapping->isCascadeRefresh,
                    'merge'   => $associationMapping->isCascadeMerge,
                    'detach'  => $associationMapping->isCascadeDetach,
                ),
            );
            
            if ($associationMapping instanceof OneToOneMapping) {
                $joinColumns = $associationMapping->joinColumns;
                $newJoinColumns = array();
                foreach ($joinColumns as $joinColumn) {
                    $newJoinColumns[$joinColumn['name']]['referencedColumnName'] = $joinColumn['referencedColumnName'];
                }
                $oneToOneMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedBy,
                    'joinColumns'   => $newJoinColumns,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);
                $array['oneToOne'][$name] = $associationMappingArray;
            } else if ($associationMapping instanceof OneToManyMapping) {
                $oneToManyMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedBy,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );

                $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
                $array['oneToMany'][$name] = $associationMappingArray;
            } else if ($associationMapping instanceof ManyToManyMapping) {
                $manyToManyMappingArray = array(
                    'mappedBy'  => $associationMapping->mappedBy,
                    'joinTable' => $associationMapping->joinTable,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
                $array['manyToMany'][$name] = $associationMappingArray;
            }
        }

        return \Symfony\Components\Yaml\Yaml::dump(array($metadata->name => $array), 10);
    }
}