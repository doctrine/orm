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

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class XmlDriver extends AbstractFileDriver
{
    protected $_fileExtension = '.dcm.xml';

    /**
     * Loads the metadata for the specified class into the provided container.
     * 
     * @param string $className
     * @param ClassMetadata $metadata
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $xmlRoot = $this->getElement($className);

        if ($xmlRoot->getName() == 'entity') {
            $metadata->setCustomRepositoryClass(
                isset($xmlRoot['repository-class']) ? (string)$xmlRoot['repository-class'] : null
            );
        } else if ($xmlRoot->getName() == 'mapped-superclass') {
            $metadata->isMappedSuperclass = true;
        } else {
            throw DoctrineException::classIsNotAValidEntityOrMapperSuperClass($className);
        }

        // Evaluate <entity...> attributes
        if (isset($xmlRoot['table'])) {
            $metadata->primaryTable['name'] = (string)$xmlRoot['table'];
        }
        
        if (isset($xmlRoot['schema'])) {
            $metadata->primaryTable['schema'] = (string)$xmlRoot['schema'];
        }
        
        if (isset($xmlRoot['inheritance-type'])) {
            $metadata->setInheritanceType((string)$xmlRoot['inheritance-type']);
        }

        // Evaluate <discriminator-column...>
        if (isset($xmlRoot->{'discriminator-column'})) {
            $discrColumn = $xmlRoot->{'discriminator-column'};
            $metadata->setDiscriminatorColumn(array(
                'name' => (string)$discrColumn['name'],
                'type' => (string)$discrColumn['type'],
                'length' => (string)$discrColumn['length']
            ));
        }

        // Evaluate <discriminator-map...>
        if (isset($xmlRoot->{'discriminator-map'})) {
            $metadata->setDiscriminatorMap((array)$xmlRoot->{'discriminator-map'});
        }

        // Evaluate <change-tracking-policy...>
        if (isset($xmlRoot->{'change-tracking-policy'})) {
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper((string)$xmlRoot->{'change-tracking-policy'})));
        }

        // Evaluate <indexes...>
        if (isset($xmlRoot->indexes)) {
            foreach ($xmlRoot->indexes->index as $index) {
                if (is_string($index['columns'])) {
                    $columns = explode(',', $index['columns']);
                } else {
                    $columns = $index['columns'];
                }
                $metadata->primaryTable['indexes'][$index['name']] = array(
                    'columns' => $columns
                );
            }
        }
        
        // Evaluate <unique-constraints..>
        if (isset($xmlRoot->{'unique-constraints'})) {
            foreach ($xmlRoot->{'unique-constraints'}->{'unique-constraint'} as $unique) {
                if (is_string($unique['columns'])) {
                    $columns = explode(',', $unique['columns']);
                } else {
                    $columns = $unique['columns'];
                }
                $metadata->primaryTable['uniqueConstraints'][] = $columns;
            }
        }

        // Evaluate <field ...> mappings
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $fieldMapping) {
                $mapping = array(
                    'fieldName' => (string)$fieldMapping['name'],
                    'type' => (string)$fieldMapping['type']
                );
                
                if (isset($fieldMapping['column'])) {
                    $mapping['columnName'] = (string)$fieldMapping['column'];
                }
                
                if (isset($fieldMapping['length'])) {
                    $mapping['length'] = (int)$fieldMapping['length'];
                }
                
                if (isset($fieldMapping['precision'])) {
                    $mapping['precision'] = (int)$fieldMapping['precision'];
                }
                
                if (isset($fieldMapping['scale'])) {
                    $mapping['scale'] = (int)$fieldMapping['scale'];
                }
                
                if (isset($fieldMapping['unique'])) {
                  $mapping['unique'] = (bool)$fieldMapping['unique'];
                }
                
                if (isset($fieldMapping['options'])) {
                    $mapping['options'] = (array)$fieldMapping['options'];
                }
                
                if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                    $metadata->setVersionMapping($mapping);
                }
                
                $metadata->mapField($mapping);
            }
        }

        // Evaluate <id ...> mappings
        foreach ($xmlRoot->id as $idElement) {
            $mapping = array(
                'id' => true,
                'fieldName' => (string)$idElement['name'],
                'type' => (string)$idElement['type']
            );
            
            if (isset($idElement['column'])) {
                $mapping['columnName'] = (string)$idElement['column'];
            }
            
            $metadata->mapField($mapping);

            if (isset($idElement->generator)) {
                $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                        . strtoupper((string)$idElement->generator['strategy'])));
            }

            // Check for SequenceGenerator/TableGenerator definition
            if (isset($idElement->{'sequence-generator'})) {
                $seqGenerator = $idElement->{'sequence-generator'};
                $metadata->setSequenceGeneratorDefinition(array(
                    'sequenceName' => $seqGenerator->{'sequence-name'},
                    'allocationSize' => $seqGenerator->{'allocation-size'},
                    'initialValue' => $seqGeneratorAnnot->{'initial-value'}
                ));
            } else if (isset($idElement->{'table-generator'})) {
                throw DoctrineException::tableIdGeneratorNotImplemented();
            }
        }

        // Evaluate <one-to-one ...> mappings
        if (isset($xmlRoot->{'one-to-one'})) {
            foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                $mapping = array(
                    'fieldName' => (string)$oneToOneElement['field'],
                    'targetEntity' => (string)$oneToOneElement['target-entity']
                );
                
                if (isset($oneToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . (string)$oneToOneElement['fetch']);
                }
                
                if (isset($oneToOneElement['mapped-by'])) {
                    $mapping['mappedBy'] = (string)$oneToOneElement['mapped-by'];
                } else {
                    $joinColumns = array();
                    
                    if (isset($oneToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->_getJoinColumnMapping($oneToOneElement->{'join-column'});
                    } else if (isset($oneToOneElement->{'join-columns'})) {
                        foreach ($oneToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                        }
                    } else {
                        throw MappingException::invalidMapping($mapping['fieldName']);
                    }
                    
                    $mapping['joinColumns'] = $joinColumns;
                }
                
                if (isset($oneToOneElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($oneToOneElement->cascade);
                }
                
                if (isset($oneToOneElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$oneToOneElement->{'orphan-removal'};
                }
                
                $metadata->mapOneToOne($mapping);
            }
        }

        // Evaluate <one-to-many ...> mappings
        if (isset($xmlRoot->{'one-to-many'})) {
            foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                $mapping = array(
                    'fieldName' => (string)$oneToManyElement['field'],
                    'targetEntity' => (string)$oneToManyElement['target-entity'],
                    'mappedBy' => (string)$oneToManyElement['mapped-by']
                );
                
                if (isset($oneToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . (string)$oneToManyElement['fetch']);
                }
                
                if (isset($oneToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($oneToManyElement->cascade);
                }
                
                if (isset($oneToManyElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$oneToManyElement->{'orphan-removal'};
                }
                
                $metadata->mapOneToMany($mapping);
            }
        }
        
        // Evaluate <many-to-one ...> mappings
        if (isset($xmlRoot->{'many-to-one'})) {
            foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                $mapping = array(
                    'fieldName' => (string)$manyToOneElement['field'],
                    'targetEntity' => (string)$manyToOneElement['target-entity']
                );
                
                if (isset($manyToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . (string)$manyToOneElement['fetch']);
                }
                
                $joinColumns = array();
                
                if (isset($manyToOneElement->{'join-column'})) {
                    $joinColumns[] = $this->_getJoinColumnMapping($manyToOneElement->{'join-column'});
                } else if (isset($manyToOneElement->{'join-columns'})) {
                    foreach ($manyToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        if (!isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $name;
                        }
                        
                        $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                } else {
                    throw MappingException::invalidMapping($mapping['fieldName']);
                }
                
                $mapping['joinColumns'] = $joinColumns;
                
                if (isset($manyToOneElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($manyToOneElement->cascade);
                }
                
                if (isset($manyToOneElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$manyToOneElement->{'orphan-removal'};
                }
                
                $metadata->mapManyToOne($mapping);
            }
        }
        
        // Evaluate <many-to-many ...> mappings
        if (isset($xmlRoot->{'many-to-many'})) {
            foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                $mapping = array(
                    'fieldName' => (string)$manyToManyElement['field'],
                    'targetEntity' => (string)$manyToManyElement['target-entity']
                );
                
                if (isset($manyToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . (string)$manyToManyElement['fetch']);
                }
                
                if (isset($manyToManyElement['mappedBy'])) {
                    $mapping['mappedBy'] = (string)$manyToManyElement['mapped-by'];
                } else if (isset($manyToManyElement->{'join-table'})) {
                    $joinTableElement = $manyToManyElement->{'join-table'};
                    $joinTable = array(
                        'name' => (string)$joinTableElement['name']
                    );
                    
                    if (isset($joinTableElement['schema'])) {
                        $joinTable['schema'] = (string)$joinTableElement['schema'];
                    }
                    
                    foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinTable['joinColumns'][] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                    
                    foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinTable['inverseJoinColumns'][] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                    
                    $mapping['joinTable'] = $joinTable;
                } else {
                    throw MappingException::invalidMapping($mapping['fieldName']);
                }
                
                if (isset($manyToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($manyToManyElement->cascade);
                }
                
                if (isset($manyToManyElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$manyToManyElement->{'orphan-removal'};
                }
                
                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate <lifecycle-callbacks...>
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $metadata->addLifecycleCallback((string)$lifecycleCallback['method'], constant('\Doctrine\ORM\Events::' . (string)$lifecycleCallback['type']));
            }
        }
    }
    
    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding SimpleXMLElement nodes.
     * 
     * @param string $file The mapping file to load.
     * @return array
     */
    protected function _loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } else if (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mapperSuperClass) {
                $className = (string)$mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        }

        return $result;
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given SimpleXMLElement.
     * 
     * @param $joinColumnElement The XML element.
     * @return array The mapping array.
     */
    private function _getJoinColumnMapping(\SimpleXMLElement $joinColumnElement)
    {
        $joinColumn = array(
            'name' => (string)$joinColumnElement['name'],
            'referencedColumnName' => (string)$joinColumnElement['referenced-column-name']
        );
        
        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = (bool)$joinColumnElement['unique'];
        }
        
        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = (bool)$joinColumnElement['nullable'];
        }
        
        if (isset($joinColumnElement['onDelete'])) {
            $joinColumn['onDelete'] = (string)$joinColumnElement['on-delete'];
        }
        
        if (isset($joinColumnElement['onUpdate'])) {
            $joinColumn['onUpdate'] = (string)$joinColumnElement['on-update'];
        }
        
        return $joinColumn;
    }
    
    /**
     * Gathers a list of cascade options found in the given cascade element.
     * 
     * @param $cascadeElement The cascade element.
     * @return array The list of cascade options.
     */
    private function _getCascadeMappings($cascadeElement)
    {
        $cascades = array();
        
        if (isset($cascadeElement->{'cascade-persist'})) {
            $cascades[] = 'persist';
        }
        
        if (isset($cascadeElement->{'cascade-remove'})) {
            $cascades[] = 'remove';
        }
        
        if (isset($cascadeElement->{'cascade-merge'})) {
            $cascades[] = 'merge';
        }
        
        if (isset($cascadeElement->{'cascade-refresh'})) {
            $cascades[] = 'refresh';
        }
        
        return $cascades;
    }
}