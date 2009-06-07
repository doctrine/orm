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

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
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
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $xmlRoot = $this->getElement($className);

        if ($xmlRoot->getName() == 'entity') {

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
                            . (string)$idElement->generator['strategy']));
                }
            }

            // Evaluate <one-to-one ...> mappings
            if (isset($xmlRoot->{'one-to-one'})) {
                foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                    $mapping = array(
                        'fieldName' => (string)$oneToOneElement['field'],
                        'targetEntity' => (string)$oneToOneElement['targetEntity']
                    );
                    if (isset($oneToOneElement['mappedBy'])) {
                        $mapping['mappedBy'] = (string)$oneToOneElement['mappedBy'];
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

                    $metadata->mapOneToOne($mapping);
                }
            }

            // Evaluate <one-to-many ...> mappings
            if (isset($xmlRoot->{'one-to-many'})) {
                foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                    $mapping = array(
                        'fieldName' => (string)$oneToManyElement['field'],
                        'targetEntity' => (string)$oneToManyElement['targetEntity'],
                        'mappedBy' => (string)$oneToManyElement['mappedBy']
                    );
                    if (isset($oneToManyElement->cascade)) {
                        $mapping['cascade'] = $this->_getCascadeMappings($oneToManyElement->cascade);
                    }
                    $metadata->mapOneToMany($mapping);
                }
            }
            
            // Evaluate <many-to-one ...> mappings
            if (isset($xmlRoot->{'many-to-one'})) {
                foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                    $mapping = array(
                        'fieldName' => (string)$manyToOneElement['field'],
                        'targetEntity' => (string)$manyToOneElement['targetEntity']
                    );
                    $joinColumns = array();
                    if (isset($manyToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->_getJoinColumnMapping($manyToOneElement->{'join-column'});
                    } else if (isset($manyToOneElement->{'join-columns'})) {
                        foreach ($manyToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                        }
                    } else {
                        throw MappingException::invalidMapping($mapping['fieldName']);
                    }
                    $mapping['joinColumns'] = $joinColumns;
                    if (isset($manyToOneElement->cascade)) {
                        $mapping['cascade'] = $this->_getCascadeMappings($manyToOneElement->cascade);
                    }
                    $metadata->mapManyToOne($mapping);
                }
            }
            
            // Evaluate <many-to-many ...> mappings
            if (isset($xmlRoot->{'many-to-many'})) {
                foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                    $mapping = array(
                        'fieldName' => (string)$manyToManyElement['field'],
                        'targetEntity' => (string)$manyToManyElement['targetEntity']
                    );
                    
                    if (isset($manyToManyElement['mappedBy'])) {
                        $mapping['mappedBy'] = (string)$manyToManyElement['mappedBy'];
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

                    $metadata->mapManyToMany($mapping);
                }
            }

        } else if ($xmlRoot->getName() == 'mapped-superclass') {
            throw MappingException::notImplemented('Mapped superclasses are not yet supported.');
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
            'referencedColumnName' => (string)$joinColumnElement['referencedColumnName']
        );
        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = (bool)$joinColumnElement['unique'];
        }
        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = (bool)$joinColumnElement['nullable'];
        }
        if (isset($joinColumnElement['onDelete'])) {
            $joinColumn['onDelete'] = (string)$joinColumnElement['onDelete'];
        }
        if (isset($joinColumnElement['onUpdate'])) {
            $joinColumn['onUpdate'] = (string)$joinColumnElement['onUpdate'];
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
        if (isset($cascadeElement->{'cascade-save'})) {
            $cascades[] = 'save';
        }
        if (isset($cascadeElement->{'cascade-delete'})) {
            $cascades[] = 'delete';
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