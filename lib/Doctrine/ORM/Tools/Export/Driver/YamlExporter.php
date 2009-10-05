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

        $array['inheritanceType'] = $this->_getInheritanceTypeString($metadata->getInheritanceType());

        if ($column = $metadata->getDiscriminatorColumn()) {
            $array['discriminatorColumn'] = $column;
        }

        if ($map = $metadata->discriminatorMap) {
            $array['discriminatorMap'] = $map;
        }

        $array['changeTrackingPolicy'] = $this->_getChangeTrackingPolicyString($metadata->changeTrackingPolicy);

        if (isset($metadata->primaryTable['indexes'])) {
            $array['indexes'] = $metadata->primaryTable['indexes'];
        }

        if (isset($metadata->primaryTable['uniqueConstraints'])) {
            $array['uniqueConstraints'] = $metadata->primaryTable['uniqueConstraints'];
        }
        
        $fields = $metadata->fieldMappings;
        
        $id = array();
        foreach ($fields as $name => $field) {
            if (isset($field['id']) && $field['id']) {
                $id[$name] = $field;
                unset($fields[$name]);
            }
        }

        if ($idGeneratorType = $this->_getIdGeneratorTypeString($metadata->generatorType)) {
            $id[$metadata->getSingleIdentifierFieldName()]['generator']['strategy'] = $this->_getIdGeneratorTypeString($metadata->generatorType);
        }
        
        $array['id'] = $id;
        $array['fields'] = $fields;

        $associations = array();
        foreach ($metadata->associationMappings as $name => $associationMapping) {
            $associationMappingArray = array(
                'fieldName'    => $associationMapping->sourceFieldName,
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
                $oneToOneMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedByFieldName,
                    'joinColumns'   => $associationMapping->joinColumns,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToOneMappingArray);
                $array['oneToOne'][$name] = $associationMappingArray;
            } else if ($associationMapping instanceof OneToManyMapping) {
                $oneToManyMappingArray = array(
                    'mappedBy'      => $associationMapping->mappedByFieldName,
                    'orphanRemoval' => $associationMapping->orphanRemoval,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $oneToManyMappingArray);
                $array['oneToMany'][$name] = $associationMappingArray;
            } else if ($associationMapping instanceof ManyToManyMapping) {
                $manyToManyMappingArray = array(
                    'mappedBy'  => $associationMapping->mappedByFieldName,
                    'joinTable' => $associationMapping->joinTable,
                );
                
                $associationMappingArray = array_merge($associationMappingArray, $manyToManyMappingArray);
                $array['manyToMany'][$name] = $associationMappingArray;
            }
        }

        return \sfYaml::dump(array($metadata->name => $array), 10);
    }
}