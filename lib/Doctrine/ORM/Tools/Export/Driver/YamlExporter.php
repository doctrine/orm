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

use Doctrine\ORM\Mapping\ClassMetadataInfo;

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
        $array['table'] = $metadata->table['name'];

        if (isset($metadata->table['schema'])) {
            $array['schema'] = $metadata->table['schema'];
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

        if (isset($metadata->table['indexes'])) {
            $array['indexes'] = $metadata->table['indexes'];
        }

        if (isset($metadata->table['uniqueConstraints'])) {
            $array['uniqueConstraints'] = $metadata->table['uniqueConstraints'];
        }
        
        $fieldMappings = $metadata->fieldMappings;
        
        $ids = array();
        foreach ($fieldMappings as $name => $fieldMapping) {
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
            $cascade = array();
            if ($associationMapping['isCascadeRemove']) {
                $cascade[] = 'remove';
            }
            if ($associationMapping['isCascadePersist']) {
                $cascade[] = 'persist';
            }
            if ($associationMapping['isCascadeRefresh']) {
                $cascade[] = 'refresh';
            }
            if ($associationMapping['isCascadeMerge']) {
                $cascade[] = 'merge';
            }
            if ($associationMapping['isCascadeDetach']) {
                $cascade[] = 'detach';
            }
            $associationMappingArray = array(
                'targetEntity' => $associationMapping['targetEntity'],
                'cascade'     => $cascade,
            );
            
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $joinColumns = $associationMapping['joinColumns'];
                $newJoinColumns = array();
                foreach ($joinColumns as $joinColumn) {
                    $newJoinColumns[$joinColumn['name']]['referencedColumnName'] = $joinColumn['referencedColumnName'];
                    if (isset($joinColumn['onDelete'])) {
                        $newJoinColumns[$joinColumn['name']]['onDelete'] = $joinColumn['onDelete'];
                    }
                    if (isset($joinColumn['onUpdate'])) {
                        $newJoinColumns[$joinColumn['name']]['onUpdate'] = $joinColumn['onUpdate'];
                    }
                }
                $oneToOneMappingArray = array(
                    'mappedBy'      => $associationMapping['mappedBy'],
                    'inversedBy'    => $associationMapping['inversedBy'],
                    'joinColumns'   => $newJoinColumns,
                    'orphanRemoval' => $associationMapping['orphanRemoval'],
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);
                $array['oneToOne'][$name] = $associationMappingArray;
            } else if ($associationMapping['type'] == ClassMetadataInfo::ONE_TO_MANY) {
                $oneToManyMappingArray = array(
                    'mappedBy'      => $associationMapping['mappedBy'],
                    'inversedBy'    => $associationMapping['inversedBy'],
                    'orphanRemoval' => $associationMapping['orphanRemoval'],
                    'orderBy' => $associationMapping['orderBy']
                );

                $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
                $array['oneToMany'][$name] = $associationMappingArray;
            } else if ($associationMapping['type'] == ClassMetadataInfo::MANY_TO_MANY) {
                $manyToManyMappingArray = array(
                    'mappedBy'   => $associationMapping['mappedBy'],
                    'inversedBy' => $associationMapping['inversedBy'],
                    'joinTable'  => $associationMapping['joinTable'],
                    'orderBy' => isset($associationMapping['orderBy']) ? $associationMapping['orderBy'] : null
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
                $array['manyToMany'][$name] = $associationMappingArray;
            }
        }
        if (isset($metadata->lifecycleCallbacks)) {
            $array['lifecycleCallbacks'] = $metadata->lifecycleCallbacks;
        }

        return \Symfony\Component\Yaml\Yaml::dump(array($metadata->name => $array), 10);
    }
}
