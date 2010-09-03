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

use SimpleXMLElement,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
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
class XmlDriver extends AbstractFileDriver
{
    /**
     * {@inheritdoc}
     */
    protected $_fileExtension = '.dcm.xml';

    /**
     * {@inheritdoc}
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
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate <entity...> attributes
        $table = array();
        if (isset($xmlRoot['table'])) {
            $table['name'] = (string)$xmlRoot['table'];
        }

        $metadata->setPrimaryTable($table);

        /* not implemented specially anyway. use table = schema.table
        if (isset($xmlRoot['schema'])) {
            $metadata->table['schema'] = (string)$xmlRoot['schema'];
        }*/
        
        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = (string)$xmlRoot['inheritance-type'];
            $metadata->setInheritanceType(constant('Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceType));

            if ($metadata->inheritanceType != \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
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
                    $map = array();
                    foreach ($xmlRoot->{'discriminator-map'}->{'discriminator-mapping'} AS $discrMapElement) {
                        $map[(string)$discrMapElement['value']] = (string)$discrMapElement['class'];
                    }
                    $metadata->setDiscriminatorMap($map);
                }
            }
        }


        // Evaluate <change-tracking-policy...>
        if (isset($xmlRoot['change-tracking-policy'])) {
            $metadata->setChangeTrackingPolicy(constant('Doctrine\ORM\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper((string)$xmlRoot['change-tracking-policy'])));
        }

        // Evaluate <indexes...>
        if (isset($xmlRoot->indexes)) {
            $metadata->table['indexes'] = array();
            foreach ($xmlRoot->indexes->index as $index) {
                $columns = explode(',', (string)$index['columns']);

                if (isset($index['name'])) {
                    $metadata->table['indexes'][(string)$index['name']] = array(
                        'columns' => $columns
                    );
                } else {
                    $metadata->table['indexes'][] = array(
                        'columns' => $columns
                    );
                }
            }
        }

        // Evaluate <unique-constraints..>
        if (isset($xmlRoot->{'unique-constraints'})) {
            $metadata->table['uniqueConstraints'] = array();
            foreach ($xmlRoot->{'unique-constraints'}->{'unique-constraint'} as $unique) {
                $columns = explode(',', (string)$unique['columns']);

                if (isset($unique['name'])) {
                    $metadata->table['uniqueConstraints'][(string)$unique['name']] = array(
                        'columns' => $columns
                    );
                } else {
                    $metadata->table['uniqueConstraints'][] = array(
                        'columns' => $columns
                    );
                }
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
                    $mapping['unique'] = ((string)$fieldMapping['unique'] == "false") ? false : true;
                }

                if (isset($fieldMapping['options'])) {
                    $mapping['options'] = (array)$fieldMapping['options'];
                }

                if (isset($fieldMapping['nullable'])) {
                    $mapping['nullable'] = ((string)$fieldMapping['nullable'] == "false") ? false : true;
                }

                if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                    $metadata->setVersionMapping($mapping);
                }

                if (isset($fieldMapping['column-definition'])) {
                    $mapping['columnDefinition'] = (string)$fieldMapping['column-definition'];
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
                $strategy = isset($idElement->generator['strategy']) ?
                        (string)$idElement->generator['strategy'] : 'AUTO';
                $metadata->setIdGeneratorType(constant('Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_'
                        . $strategy));
            }

            // Check for SequenceGenerator/TableGenerator definition
            if (isset($idElement->{'sequence-generator'})) {
                $seqGenerator = $idElement->{'sequence-generator'};
                $metadata->setSequenceGeneratorDefinition(array(
                    'sequenceName' => (string)$seqGenerator['sequence-name'],
                    'allocationSize' => (string)$seqGenerator['allocation-size'],
                    'initialValue' => (string)$seqGenerator['initial-value']
                ));
            } else if (isset($idElement->{'table-generator'})) {
                throw MappingException::tableIdGeneratorNotImplemented($className);
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
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string)$oneToOneElement['fetch']);
                }

                if (isset($oneToOneElement['mapped-by'])) {
                    $mapping['mappedBy'] = (string)$oneToOneElement['mapped-by'];
                } else {
                    if (isset($oneToOneElement['inversed-by'])) {
                        $mapping['inversedBy'] = (string)$oneToOneElement['inversed-by'];
                    }
                    $joinColumns = array();

                    if (isset($oneToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->_getJoinColumnMapping($oneToOneElement->{'join-column'});
                    } else if (isset($oneToOneElement->{'join-columns'})) {
                        foreach ($oneToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumns[] = $this->_getJoinColumnMapping($joinColumnElement);
                        }
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
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string)$oneToManyElement['fetch']);
                }

                if (isset($oneToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($oneToManyElement->cascade);
                }

                if (isset($oneToManyElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$oneToManyElement->{'orphan-removal'};
                }

                if (isset($oneToManyElement->{'order-by'})) {
                    $orderBy = array();
                    foreach ($oneToManyElement->{'order-by'}->{'order-by-field'} AS $orderByField) {
                        $orderBy[(string)$orderByField['name']] = (string)$orderByField['direction'];
                    }
                    $mapping['orderBy'] = $orderBy;
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
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string)$manyToOneElement['fetch']);
                }

                if (isset($manyToOneElement['inversed-by'])) {
                    $mapping['inversedBy'] = (string)$manyToOneElement['inversed-by'];
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
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string)$manyToManyElement['fetch']);
                }

                if (isset($manyToManyElement['mapped-by'])) {
                    $mapping['mappedBy'] = (string)$manyToManyElement['mapped-by'];
                } else if (isset($manyToManyElement->{'join-table'})) {
                    if (isset($manyToManyElement['inversed-by'])) {
                        $mapping['inversedBy'] = (string)$manyToManyElement['inversed-by'];
                    }

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
                }

                if (isset($manyToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($manyToManyElement->cascade);
                }

                if (isset($manyToManyElement->{'orphan-removal'})) {
                    $mapping['orphanRemoval'] = (bool)$manyToManyElement->{'orphan-removal'};
                }

                if (isset($manyToManyElement->{'order-by'})) {
                    $orderBy = array();
                    foreach ($manyToManyElement->{'order-by'}->{'order-by-field'} AS $orderByField) {
                        $orderBy[(string)$orderByField['name']] = (string)$orderByField['direction'];
                    }
                    $mapping['orderBy'] = $orderBy;
                }

                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate <lifecycle-callbacks...>
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $metadata->addLifecycleCallback((string)$lifecycleCallback['method'], constant('Doctrine\ORM\Events::' . (string)$lifecycleCallback['type']));
            }
        }
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given SimpleXMLElement.
     *
     * @param $joinColumnElement The XML element.
     * @return array The mapping array.
     */
    private function _getJoinColumnMapping(SimpleXMLElement $joinColumnElement)
    {
        $joinColumn = array(
            'name' => (string)$joinColumnElement['name'],
            'referencedColumnName' => (string)$joinColumnElement['referenced-column-name']
        );

        if (isset($joinColumnElement['unique'])) {
            $joinColumn['unique'] = ((string)$joinColumnElement['unique'] == "false") ? false : true;
        }

        if (isset($joinColumnElement['nullable'])) {
            $joinColumn['nullable'] = ((string)$joinColumnElement['nullable'] == "false") ? false : true;
        }

        if (isset($joinColumnElement['on-delete'])) {
            $joinColumn['onDelete'] = (string)$joinColumnElement['on-delete'];
        }

        if (isset($joinColumnElement['on-update'])) {
            $joinColumn['onUpdate'] = (string)$joinColumnElement['on-update'];
        }

        if (isset($joinColumnElement['column-definition'])) {
            $joinColumn['columnDefinition'] = (string)$joinColumnElement['column-definition'];
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
        foreach ($cascadeElement->children() as $action) {
            // According to the JPA specifications, XML uses "cascade-persist"
            // instead of "persist". Here, both variations
            // are supported because both YAML and Annotation use "persist"
            // and we want to make sure that this driver doesn't need to know
            // anything about the supported cascading actions
            $cascades[] = str_replace('cascade-', '', $action->getName());
        }
        return $cascades;
    }

    /**
     * {@inheritdoc}
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
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = (string)$mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        }

        return $result;
    }
}
