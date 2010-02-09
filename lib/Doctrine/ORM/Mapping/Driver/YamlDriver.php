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

if ( ! class_exists('sfYaml', false)) {
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYaml.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlDumper.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlInline.class.php';
    require_once __DIR__ . '/../../../../vendor/sfYaml/sfYamlParser.class.php';
}

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @license 	http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    	www.doctrine-project.org
 * @since   	2.0
 * @version     $Revision$
 * @author		Benjamin Eberlei <kontakt@beberlei.de>
 * @author		Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class YamlDriver extends AbstractFileDriver
{
    /**
     * {@inheritdoc}
     */
    protected $_fileExtension = '.dcm.yml';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $element = $this->getElement($className);
        
        if ($element['type'] == 'entity') {
            $metadata->setCustomRepositoryClass(
                isset($element['repositoryClass']) ? $element['repositoryClass'] : null
            );
        } else if ($element['type'] == 'mappedSuperclass') {
            $metadata->isMappedSuperclass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMapperSuperClass($className);
        }

        // Evaluate root level properties
        if (isset($element['table'])) {
            $metadata->primaryTable['name'] = $element['table'];
        }
        
        if (isset($element['schema'])) {
            $metadata->primaryTable['schema'] = $element['schema'];
        }
        
        if (isset($element['inheritanceType'])) {
            $metadata->setInheritanceType(constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($element['inheritanceType'])));
        }

        // Evaluate discriminatorColumn
        if (isset($element['discriminatorColumn'])) {
            $discrColumn = $element['discriminatorColumn'];
            $metadata->setDiscriminatorColumn(array(
                'name' => $discrColumn['name'],
                'type' => $discrColumn['type'],
                'length' => $discrColumn['length']
            ));
        }

        // Evaluate discriminatorMap
        if (isset($element['discriminatorMap'])) {
            $metadata->setDiscriminatorMap($element['discriminatorMap']);
        }

        // Evaluate changeTrackingPolicy
        if (isset($element['changeTrackingPolicy'])) {
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper($element['changeTrackingPolicy'])));
        }

        // Evaluate indexes
        if (isset($element['indexes'])) {
            foreach ($element['indexes'] as $name => $index) {
                if ( ! isset($index['name'])) {
                    $index['name'] = $name;
                }
                
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

        // Evaluate uniqueConstraints
        if (isset($element['uniqueConstraints'])) {
            foreach ($element['uniqueConstraints'] as $name => $unique) {
                if ( ! isset($unique['name'])) {
                    $unique['name'] = $name;
                }
                
                if (is_string($unique['columns'])) {
                    $columns = explode(',', $unique['columns']);
                } else {
                    $columns = $unique['columns'];
                }
                
                $metadata->primaryTable['uniqueConstraints'][$unique['name']] = array(
                    'columns' => $columns
                );
            }
        }

        if (isset($element['id'])) {
            // Evaluate identifier settings
            foreach ($element['id'] as $name => $idElement) {
                $mapping = array(
                    'id' => true,
                    'fieldName' => $name,
                    'type' => $idElement['type']
                );
            
                if (isset($idElement['column'])) {
                    $mapping['columnName'] = $idElement['column'];
                }
            
                $metadata->mapField($mapping);

                if (isset($idElement['generator'])) {
                    $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                            . strtoupper($idElement['generator']['strategy'])));
                }
            }
        }

        // Evaluate fields
        if (isset($element['fields'])) {
            foreach ($element['fields'] as $name => $fieldMapping) {
                if (!isset($fieldMapping['type'])) {
                    throw MappingException::propertyTypeIsRequired($className, $name);
                }

                $e = explode('(', $fieldMapping['type']);
                $fieldMapping['type'] = $e[0];
                if (isset($e[1])) {
                    $fieldMapping['length'] = substr($e[1], 0, strlen($e[1]) - 1);
                }
                $mapping = array(
                    'fieldName' => $name,
                    'type' => $fieldMapping['type']
                );
                if (isset($fieldMapping['id'])) {
                    $mapping['id'] = true;
                    if (isset($fieldMapping['generator']['strategy'])) {
                        $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                                . strtoupper($fieldMapping['generator']['strategy'])));
                    }
                }
                // Check for SequenceGenerator/TableGenerator definition
                if (isset($fieldMapping['sequenceGenerator'])) {
                    $metadata->setSequenceGeneratorDefinition($fieldMapping['sequenceGenerator']);
                } else if (isset($fieldMapping['tableGenerator'])) {
                    throw MappingException::tableIdGeneratorNotImplemented($className);
                }
                if (isset($fieldMapping['column'])) {
                    $mapping['columnName'] = $fieldMapping['column'];
                }
                if (isset($fieldMapping['length'])) {
                    $mapping['length'] = $fieldMapping['length'];
                }
                if (isset($fieldMapping['precision'])) {
                    $mapping['precision'] = $fieldMapping['precision'];
                }
                if (isset($fieldMapping['scale'])) {
                    $mapping['scale'] = $fieldMapping['scale'];
                }
                if (isset($fieldMapping['unique'])) {
                    $mapping['unique'] = (bool)$fieldMapping['unique'];
                }
                if (isset($fieldMapping['options'])) {
                    $mapping['options'] = $fieldMapping['options'];
                }
                if (isset($fieldMapping['nullable'])) {
                    $mapping['nullable'] = $fieldMapping['nullable'];
                }
                if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                    $metadata->setVersionMapping($mapping);
                }
                if (isset($fieldMapping['columnDefinition'])) {
                    $mapping['columnDefinition'] = $fieldMapping['columnDefinition'];
                }
                
                $metadata->mapField($mapping);
            }
        }

        // Evaluate oneToOne relationships
        if (isset($element['oneToOne'])) {
            foreach ($element['oneToOne'] as $name => $oneToOneElement) {
                $mapping = array(
                    'fieldName' => $name,
                    'targetEntity' => $oneToOneElement['targetEntity']
                );
                
                if (isset($oneToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . $oneToOneElement['fetch']);
                }
                
                if (isset($oneToOneElement['mappedBy'])) {
                    $mapping['mappedBy'] = $oneToOneElement['mappedBy'];
                } else {
                    $joinColumns = array();
                    
                    if (isset($oneToOneElement['joinColumn'])) {
                        $joinColumns[] = $this->_getJoinColumnMapping($oneToOneElement['joinColumn']);
                    } else if (isset($oneToOneElement['joinColumns'])) {
                        foreach ($oneToOneElement['joinColumns'] as $name => $joinColumnElement) {
                            if (!isset($joinColumnElement['name'])) {
                                $joinColumnElement['name'] = $name;
                            }
                            
                            $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                        }
                    }
                    
                    $mapping['joinColumns'] = $joinColumns;
                }

                if (isset($oneToOneElement['cascade'])) {
                    $mapping['cascade'] = $oneToOneElement['cascade'];
                }

                $metadata->mapOneToOne($mapping);
            }
        }

        // Evaluate oneToMany relationships
        if (isset($element['oneToMany'])) {
            foreach ($element['oneToMany'] as $name => $oneToManyElement) {
                $mapping = array(
                    'fieldName' => $name,
                    'targetEntity' => $oneToManyElement['targetEntity'],
                    'mappedBy' => $oneToManyElement['mappedBy']
                );
                
                if (isset($oneToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . $oneToManyElement['fetch']);
                }
                
                if (isset($oneToManyElement['cascade'])) {
                    $mapping['cascade'] = $oneToManyElement['cascade'];
                }
                
                $metadata->mapOneToMany($mapping);
            }
        }

        // Evaluate manyToOne relationships
        if (isset($element['manyToOne'])) {
            foreach ($element['manyToOne'] as $name => $manyToOneElement) {
                $mapping = array(
                    'fieldName' => $name, 
                    'targetEntity' => $manyToOneElement['targetEntity']
                );
                
                if (isset($manyToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . $manyToOneElement['fetch']);
                }
                
                $joinColumns = array();
                
                if (isset($manyToOneElement['joinColumn'])) {
                    $joinColumns[] = $this->_getJoinColumnMapping($manyToOneElement['joinColumn']);
                } else if (isset($manyToOneElement['joinColumns'])) {
                    foreach ($manyToOneElement['joinColumns'] as $name => $joinColumnElement) {
                        if (!isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $name;
                        }
                        
                        $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                }
                
                $mapping['joinColumns'] = $joinColumns;
                
                if (isset($manyToOneElement['cascade'])) {
                    $mapping['cascade'] = $manyToOneElement['cascade'];
                }
                
                $metadata->mapManyToOne($mapping);
            }
        }

        // Evaluate manyToMany relationships
        if (isset($element['manyToMany'])) {
            foreach ($element['manyToMany'] as $name => $manyToManyElement) {
                $mapping = array(
                    'fieldName' => $name,
                    'targetEntity' => $manyToManyElement['targetEntity']
                );
                
                if (isset($manyToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\AssociationMapping::FETCH_' . $manyToManyElement['fetch']);
                }
                
                if (isset($manyToManyElement['mappedBy'])) {
                    $mapping['mappedBy'] = $manyToManyElement['mappedBy'];
                } else if (isset($manyToManyElement['joinTable'])) {
                    $joinTableElement = $manyToManyElement['joinTable'];
                    $joinTable = array(
                        'name' => $joinTableElement['name']
                    );
                    
                    if (isset($joinTableElement['schema'])) {
                        $joinTable['schema'] = $joinTableElement['schema'];
                    }
                    
                    foreach ($joinTableElement['joinColumns'] as $name => $joinColumnElement) {
                        if (!isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $name;
                        }
                        
                        $joinTable['joinColumns'][] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                    
                    foreach ($joinTableElement['inverseJoinColumns'] as $name => $joinColumnElement) {
                        if (!isset($joinColumnElement['name'])) {
                            $joinColumnElement['name'] = $name;
                        }
                        
                        $joinTable['inverseJoinColumns'][] = $this->_getJoinColumnMapping($joinColumnElement);
                    }
                    
                    $mapping['joinTable'] = $joinTable;
                }
                
                if (isset($manyToManyElement['cascade'])) {
                    $mapping['cascade'] = $manyToManyElement['cascade'];
                }

                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate lifeCycleCallbacks
        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $method => $type) {
                $metadata->addLifecycleCallback($method, constant('\Doctrine\ORM\Events::' . $type));
            }
        }
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given join column element.
     * 
     * @param $joinColumnElement The array join column element
     * @return array The mapping array.
     */
    private function _getJoinColumnMapping($joinColumnElement)
    {
        $joinColumn = array(
            'name' => $joinColumnElement['name'],
            'referencedColumnName' => $joinColumnElement['referencedColumnName']
        );
                
          if (isset($joinColumnElement['fieldName'])) {
              $joinColumn['fieldName'] = (string) $joinColumnElement['fieldName'];
          }
        
        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = (bool) $joinColumnElement['unique'];
        }
        
        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = (bool) $joinColumnElement['nullable'];
        }
        
        if (isset($joinColumnElement['onDelete'])) {
            $joinColumn['onDelete'] = $joinColumnElement['onDelete'];
        }
        
        if (isset($joinColumnElement['onUpdate'])) {
            $joinColumn['onUpdate'] = $joinColumnElement['onUpdate'];
        }

        if (isset($joinColumnElement['columnDefinition'])) {
            $joinColumn['columnDefinition'] = $joinColumnElement['columnDefinition'];
        }
        
        return $joinColumn;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        return \sfYaml::load($file);
    }
}