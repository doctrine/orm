<?php
/*
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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\TableMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\VersionFieldMetadata;
use SimpleXMLElement;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 *
 * @license 	http://www.opensource.org/licenses/mit-license.php MIT
 * @link    	www.doctrine-project.org
 * @since   	2.0
 * @author		Benjamin Eberlei <kontakt@beberlei.de>
 * @author		Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /* @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        /* @var \SimpleXMLElement $xmlRoot */
        $xmlRoot = $this->getElement($className);

        if ($xmlRoot->getName() === 'entity') {
            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClass((string) $xmlRoot['repository-class']);
            }
            if (isset($xmlRoot['read-only']) && $this->evaluateBoolean($xmlRoot['read-only'])) {
                $metadata->markReadOnly();
            }
        } else if ($xmlRoot->getName() === 'mapped-superclass') {
            $metadata->setCustomRepositoryClass(
                isset($xmlRoot['repository-class']) ? (string) $xmlRoot['repository-class'] : null
            );
            $metadata->isMappedSuperclass = true;
        } else if ($xmlRoot->getName() === 'embeddable') {
            $metadata->isEmbeddedClass = true;
        } else {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Evaluate <entity...> attributes
        $tableBuilder = new TableMetadataBuilder();

        if (isset($xmlRoot['table'])) {
            $metadata->table->setName((string) $xmlRoot['table']);
        }

        if (isset($xmlRoot['schema'])) {
            $metadata->table->setSchema((string) $xmlRoot['schema']);
        }

        if (isset($xmlRoot->options)) {
            $options = $this->parseOptions($xmlRoot->options->children());

            $tableBuilder->withOptions($options);
        }

        if (! empty($metadata->table->getSchema())) {
            $tableBuilder->withSchema($metadata->table->getSchema());
        }

        $tableBuilder->withName($metadata->table->getName());

        // Evaluate <indexes...>
        if (isset($xmlRoot->indexes)) {
            foreach ($xmlRoot->indexes->index as $indexXml) {
                $indexName = isset($indexXml['name']) ? (string) $indexXml['name'] : null;
                $columns   = explode(',', (string) $indexXml['columns']);
                $isUnique  = isset($indexXml['unique']) && $indexXml['unique'];
                $options   = isset($indexXml->options) ? $this->parseOptions($indexXml->options->children()) : [];
                $flags     = isset($indexXml['flags']) ? explode(',', (string) $indexXml['flags']) : [];

                $tableBuilder->withIndex($indexName, $columns, $isUnique, $options, $flags);
            }
        }

        // Evaluate <unique-constraints..>
        if (isset($xmlRoot->{'unique-constraints'})) {
            foreach ($xmlRoot->{'unique-constraints'}->{'unique-constraint'} as $uniqueXml) {
                $indexName = isset($uniqueXml['name']) ? (string) $uniqueXml['name'] : null;
                $columns   = explode(',', (string) $uniqueXml['columns']);
                $options   = isset($uniqueXml->options) ? $this->parseOptions($uniqueXml->options->children()) : [];
                $flags     = isset($uniqueXml['flags']) ? explode(',', (string) $uniqueXml['flags']) : [];

                $tableBuilder->withUniqueConstraint($indexName, $columns, $options, $flags);
            }
        }

        $metadata->setPrimaryTable($tableBuilder->build());

        // Evaluate second level cache
        if (isset($xmlRoot->cache)) {
            $metadata->enableCache($this->cacheToArray($xmlRoot->cache));
        }

        // Evaluate named queries
        if (isset($xmlRoot->{'named-queries'})) {
            foreach ($xmlRoot->{'named-queries'}->{'named-query'} as $namedQueryElement) {
                $metadata->addNamedQuery(
                    [
                        'name'  => (string) $namedQueryElement['name'],
                        'query' => (string) $namedQueryElement['query']
                    ]
                );
            }
        }

        // Evaluate native named queries
        if (isset($xmlRoot->{'named-native-queries'})) {
            foreach ($xmlRoot->{'named-native-queries'}->{'named-native-query'} as $nativeQueryElement) {
                $metadata->addNamedNativeQuery(
                    [
                        'name'              => isset($nativeQueryElement['name']) ? (string) $nativeQueryElement['name'] : null,
                        'query'             => isset($nativeQueryElement->query) ? (string) $nativeQueryElement->query : null,
                        'resultClass'       => isset($nativeQueryElement['result-class']) ? (string) $nativeQueryElement['result-class'] : null,
                        'resultSetMapping'  => isset($nativeQueryElement['result-set-mapping']) ? (string) $nativeQueryElement['result-set-mapping'] : null,
                    ]
                );
            }
        }

        // Evaluate sql result set mapping
        if (isset($xmlRoot->{'sql-result-set-mappings'})) {
            foreach ($xmlRoot->{'sql-result-set-mappings'}->{'sql-result-set-mapping'} as $rsmElement) {
                $entities   = [];
                $columns    = [];
                foreach ($rsmElement as $entityElement) {
                    //<entity-result/>
                    if (isset($entityElement['entity-class'])) {
                        $entityResult = [
                            'fields'                => [],
                            'entityClass'           => (string) $entityElement['entity-class'],
                            'discriminatorColumn'   => isset($entityElement['discriminator-column']) ? (string) $entityElement['discriminator-column'] : null,
                        ];

                        foreach ($entityElement as $fieldElement) {
                            $entityResult['fields'][] = [
                                'name'      => isset($fieldElement['name']) ? (string) $fieldElement['name'] : null,
                                'column'    => isset($fieldElement['column']) ? (string) $fieldElement['column'] : null,
                            ];
                        }

                        $entities[] = $entityResult;
                    }

                    //<column-result/>
                    if (isset($entityElement['name'])) {
                        $columns[] = [
                            'name' => (string) $entityElement['name'],
                        ];
                    }
                }

                $metadata->addSqlResultSetMapping(
                    [
                        'name'          => (string) $rsmElement['name'],
                        'entities'      => $entities,
                        'columns'       => $columns
                    ]
                );
            }
        }

        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = strtoupper((string) $xmlRoot['inheritance-type']);

            $metadata->setInheritanceType(
                constant(sprintf('%s::INHERITANCE_TYPE_%s', ClassMetadata::class, $inheritanceType))
            );

            if ($metadata->inheritanceType !== \Doctrine\ORM\Mapping\ClassMetadata::INHERITANCE_TYPE_NONE) {
                $discriminatorColumnBuilder = new DiscriminatorColumnMetadataBuilder();

                $discriminatorColumnBuilder->withTableName($metadata->getTableName());

                // Evaluate <discriminator-column...>
                if (isset($xmlRoot->{'discriminator-column'})) {
                    $discriminatorColumnMapping = $xmlRoot->{'discriminator-column'};
                    $typeName                   = isset($discriminatorColumnMapping['type'])
                        ? (string) $discriminatorColumnMapping['type']
                        : 'string';

                    $discriminatorColumnBuilder->withType(Type::getType($typeName));
                    $discriminatorColumnBuilder->withColumnName((string) $discriminatorColumnMapping['name']);

                    if (isset($discriminatorColumnMapping['column-definition'])) {
                        $discriminatorColumnBuilder->withColumnDefinition((string) $discriminatorColumnMapping['column-definition']);
                    }

                    if (isset($discriminatorColumnMapping['length'])) {
                        $discriminatorColumnBuilder->withLength((int) $discriminatorColumnMapping['length']);
                    }
                }

                $discriminatorColumn = $discriminatorColumnBuilder->build();

                $metadata->setDiscriminatorColumn($discriminatorColumn);

                // Evaluate <discriminator-map...>
                if (isset($xmlRoot->{'discriminator-map'})) {
                    $map = [];

                    foreach ($xmlRoot->{'discriminator-map'}->{'discriminator-mapping'} as $discrMapElement) {
                        $map[(string) $discrMapElement['value']] = (string) $discrMapElement['class'];
                    }

                    $metadata->setDiscriminatorMap($map);
                }
            }
        }


        // Evaluate <change-tracking-policy...>
        if (isset($xmlRoot['change-tracking-policy'])) {
            $changeTrackingPolicy = strtoupper((string) $xmlRoot['change-tracking-policy']);

            $metadata->setChangeTrackingPolicy(
                constant(sprintf('%s::CHANGETRACKING_%s', ClassMetadata::class, $changeTrackingPolicy))
            );
        }

        // Evaluate <field ...> mappings
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $fieldElement) {
                $fieldName        = (string) $fieldElement['name'];
                $isFieldVersioned = isset($fieldElement['version']) && $fieldElement['version'];
                $fieldMetadata    = $this->convertFieldElementToFieldMetadata($fieldElement, $fieldName, $isFieldVersioned);

                $metadata->addProperty($fieldMetadata);

                if ($fieldMetadata instanceof VersionFieldMetadata) {
                    $metadata->setVersionProperty($fieldMetadata);
                }
            }
        }

        if (isset($xmlRoot->embedded)) {
            foreach ($xmlRoot->embedded as $embeddedMapping) {
                $columnPrefix = isset($embeddedMapping['column-prefix'])
                    ? (string) $embeddedMapping['column-prefix']
                    : null;

                $useColumnPrefix = isset($embeddedMapping['use-column-prefix'])
                    ? $this->evaluateBoolean($embeddedMapping['use-column-prefix'])
                    : true;

                $mapping = [
                    'fieldName' => (string) $embeddedMapping['name'],
                    'class' => (string) $embeddedMapping['class'],
                    'columnPrefix' => $useColumnPrefix ? $columnPrefix : false
                ];

                $metadata->mapEmbedded($mapping);
            }
        }

        // Evaluate <id ...> mappings
        $associationIds = [];

        foreach ($xmlRoot->id as $idElement) {
            $fieldName = (string) $idElement['name'];

            if (isset($idElement['association-key']) && $this->evaluateBoolean($idElement['association-key'])) {
                $associationIds[$fieldName] = true;

                continue;
            }

            $fieldMetadata = $this->convertFieldElementToFieldMetadata($idElement, $fieldName, false);

            $fieldMetadata->setPrimaryKey(true);

            if (isset($idElement->generator)) {
                $strategy = isset($idElement->generator['strategy'])
                    ? (string) $idElement->generator['strategy']
                    : 'AUTO'
                ;

                $metadata->setIdGeneratorType(
                    constant(sprintf('%s::GENERATOR_TYPE_%s', ClassMetadata::class, strtoupper($strategy)))
                );
            }

            // Check for SequenceGenerator/TableGenerator definition
            if (isset($idElement->{'sequence-generator'})) {
                $seqGenerator = $idElement->{'sequence-generator'};

                $metadata->setGeneratorDefinition(
                    [
                        'sequenceName'   => (string) $seqGenerator['sequence-name'],
                        'allocationSize' => (string) $seqGenerator['allocation-size'],
                    ]
                );
            } else if (isset($idElement->{'custom-id-generator'})) {
                $customGenerator = $idElement->{'custom-id-generator'};

                $metadata->setGeneratorDefinition(
                    [
                        'class'     => (string) $customGenerator['class'],
                        'arguments' => [],
                    ]
                );
            } else if (isset($idElement->{'table-generator'})) {
                throw MappingException::tableIdGeneratorNotImplemented($className);
            }

            $metadata->addProperty($fieldMetadata);
        }

        // Evaluate <one-to-one ...> mappings
        if (isset($xmlRoot->{'one-to-one'})) {
            foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                $mapping = [
                    'fieldName' => (string) $oneToOneElement['field'],
                    'targetEntity' => (string) $oneToOneElement['target-entity']
                ];

                if (isset($associationIds[$mapping['fieldName']])) {
                    $mapping['id'] = true;
                }

                if (isset($oneToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string) $oneToOneElement['fetch']);
                }

                if (isset($oneToOneElement['mapped-by'])) {
                    $mapping['mappedBy'] = (string) $oneToOneElement['mapped-by'];
                } else {
                    if (isset($oneToOneElement['inversed-by'])) {
                        $mapping['inversedBy'] = (string) $oneToOneElement['inversed-by'];
                    }

                    $joinColumns = [];

                    if (isset($oneToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($oneToOneElement->{'join-column'});
                    } else if (isset($oneToOneElement->{'join-columns'})) {
                        foreach ($oneToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                        }
                    }

                    $mapping['joinColumns'] = $joinColumns;
                }

                if (isset($oneToOneElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($oneToOneElement->cascade);
                }

                if (isset($oneToOneElement['orphan-removal'])) {
                    $mapping['orphanRemoval'] = $this->evaluateBoolean($oneToOneElement['orphan-removal']);
                }

                // Evaluate second level cache
                if (isset($oneToOneElement->cache)) {
                    $mapping['cache'] = $metadata->getAssociationCacheDefaults($mapping['fieldName'], $this->cacheToArray($oneToOneElement->cache));
                }

                $metadata->mapOneToOne($mapping);
            }
        }

        // Evaluate <one-to-many ...> mappings
        if (isset($xmlRoot->{'one-to-many'})) {
            foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                $mapping = [
                    'fieldName' => (string) $oneToManyElement['field'],
                    'targetEntity' => (string) $oneToManyElement['target-entity'],
                    'mappedBy' => (string) $oneToManyElement['mapped-by']
                ];

                if (isset($oneToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string) $oneToManyElement['fetch']);
                }

                if (isset($oneToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($oneToManyElement->cascade);
                }

                if (isset($oneToManyElement['orphan-removal'])) {
                    $mapping['orphanRemoval'] = $this->evaluateBoolean($oneToManyElement['orphan-removal']);
                }

                if (isset($oneToManyElement->{'order-by'})) {
                    $orderBy = [];
                    foreach ($oneToManyElement->{'order-by'}->{'order-by-field'} as $orderByField) {
                        $orderBy[(string) $orderByField['name']] = (string) $orderByField['direction'];
                    }
                    $mapping['orderBy'] = $orderBy;
                }

                if (isset($oneToManyElement['index-by'])) {
                    $mapping['indexBy'] = (string) $oneToManyElement['index-by'];
                } else if (isset($oneToManyElement->{'index-by'})) {
                    throw new \InvalidArgumentException("<index-by /> is not a valid tag");
                }

                // Evaluate second level cache
                if (isset($oneToManyElement->cache)) {
                    $mapping['cache'] = $metadata->getAssociationCacheDefaults($mapping['fieldName'], $this->cacheToArray($oneToManyElement->cache));
                }

                $metadata->mapOneToMany($mapping);
            }
        }

        // Evaluate <many-to-one ...> mappings
        if (isset($xmlRoot->{'many-to-one'})) {
            foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                $mapping = [
                    'fieldName' => (string) $manyToOneElement['field'],
                    'targetEntity' => (string) $manyToOneElement['target-entity']
                ];

                if (isset($associationIds[$mapping['fieldName']])) {
                    $mapping['id'] = true;
                }

                if (isset($manyToOneElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string) $manyToOneElement['fetch']);
                }

                if (isset($manyToOneElement['inversed-by'])) {
                    $mapping['inversedBy'] = (string) $manyToOneElement['inversed-by'];
                }

                $joinColumns = [];

                if (isset($manyToOneElement->{'join-column'})) {
                    $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($manyToOneElement->{'join-column'});
                } else if (isset($manyToOneElement->{'join-columns'})) {
                    foreach ($manyToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }
                }

                $mapping['joinColumns'] = $joinColumns;

                if (isset($manyToOneElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($manyToOneElement->cascade);
                }

                // Evaluate second level cache
                if (isset($manyToOneElement->cache)) {
                    $mapping['cache'] = $metadata->getAssociationCacheDefaults($mapping['fieldName'], $this->cacheToArray($manyToOneElement->cache));
                }

                $metadata->mapManyToOne($mapping);

            }
        }

        // Evaluate <many-to-many ...> mappings
        if (isset($xmlRoot->{'many-to-many'})) {
            foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                $mapping = [
                    'fieldName' => (string) $manyToManyElement['field'],
                    'targetEntity' => (string) $manyToManyElement['target-entity']
                ];

                if (isset($manyToManyElement['fetch'])) {
                    $mapping['fetch'] = constant('Doctrine\ORM\Mapping\ClassMetadata::FETCH_' . (string) $manyToManyElement['fetch']);
                }

                if (isset($manyToManyElement['orphan-removal'])) {
                    $mapping['orphanRemoval'] = $this->evaluateBoolean($manyToManyElement['orphan-removal']);
                }

                if (isset($manyToManyElement['mapped-by'])) {
                    $mapping['mappedBy'] = (string) $manyToManyElement['mapped-by'];
                } else if (isset($manyToManyElement->{'join-table'})) {
                    if (isset($manyToManyElement['inversed-by'])) {
                        $mapping['inversedBy'] = (string) $manyToManyElement['inversed-by'];
                    }

                    $joinTableElement = $manyToManyElement->{'join-table'};
                    $joinTable = [
                        'name' => (string) $joinTableElement['name']
                    ];

                    if (isset($joinTableElement['schema'])) {
                        $joinTable['schema'] = (string) $joinTableElement['schema'];
                    }

                    foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinTable['joinColumns'][] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }

                    foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinTable['inverseJoinColumns'][] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }

                    $mapping['joinTable'] = $joinTable;
                }

                if (isset($manyToManyElement->cascade)) {
                    $mapping['cascade'] = $this->_getCascadeMappings($manyToManyElement->cascade);
                }

                if (isset($manyToManyElement->{'order-by'})) {
                    $orderBy = [];

                    foreach ($manyToManyElement->{'order-by'}->{'order-by-field'} as $orderByField) {
                        $orderBy[(string) $orderByField['name']] = (string) $orderByField['direction'];
                    }

                    $mapping['orderBy'] = $orderBy;
                }

                if (isset($manyToManyElement['index-by'])) {
                    $mapping['indexBy'] = (string) $manyToManyElement['index-by'];
                } else if (isset($manyToManyElement->{'index-by'})) {
                    throw new \InvalidArgumentException("<index-by /> is not a valid tag");
                }

                // Evaluate second level cache
                if (isset($manyToManyElement->cache)) {
                    $mapping['cache'] = $metadata->getAssociationCacheDefaults($mapping['fieldName'], $this->cacheToArray($manyToManyElement->cache));
                }

                $metadata->mapManyToMany($mapping);
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'attribute-overrides'})) {
            foreach ($xmlRoot->{'attribute-overrides'}->{'attribute-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];

                foreach ($overrideElement->field as $fieldElement) {
                    $fieldMetadata = $this->convertFieldElementToFieldMetadata($fieldElement, $fieldName, false);

                    $metadata->setAttributeOverride($fieldMetadata);
                }
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'association-overrides'})) {
            foreach ($xmlRoot->{'association-overrides'}->{'association-override'} as $overrideElement) {
                $fieldName  = (string) $overrideElement['name'];
                $override   = [];

                // Check for join-columns
                if (isset($overrideElement->{'join-columns'})) {
                    $joinColumns = [];

                    foreach ($overrideElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }

                    $override['joinColumns'] = $joinColumns;
                }

                // Check for join-table
                if ($overrideElement->{'join-table'}) {
                    $joinTable          = null;
                    $joinTableElement   = $overrideElement->{'join-table'};

                    $joinTable = [
                        'name'      => (string) $joinTableElement['name'],
                        'schema'    => (string) $joinTableElement['schema']
                    ];

                    if (isset($joinTableElement->{'join-columns'})) {
                        foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinTable['joinColumns'][] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                        }
                    }

                    if (isset($joinTableElement->{'inverse-join-columns'})) {
                        foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinTable['inverseJoinColumns'][] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                        }
                    }

                    $override['joinTable'] = $joinTable;
                }

                // Check for inversed-by
                if (isset($overrideElement->{'inversed-by'})) {
                    $override['inversedBy'] = (string) $overrideElement->{'inversed-by'}['name'];
                }

                // Check for `fetch`
                if (isset($overrideElement['fetch'])) {
                    $override['fetch'] = constant(Metadata::class . '::FETCH_' . (string) $overrideElement['fetch']);
                }

                $metadata->setAssociationOverride($fieldName, $override);
            }
        }

        // Evaluate <lifecycle-callbacks...>
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $metadata->addLifecycleCallback((string) $lifecycleCallback['method'], constant('Doctrine\ORM\Events::' . (string) $lifecycleCallback['type']));
            }
        }

        // Evaluate entity listener
        if (isset($xmlRoot->{'entity-listeners'})) {
            foreach ($xmlRoot->{'entity-listeners'}->{'entity-listener'} as $listenerElement) {
                $className = (string) $listenerElement['class'];

                // Evaluate the listener using naming convention.
                if ($listenerElement->count() === 0) {
                    EntityListenerBuilder::bindEntityListener($metadata, $className);

                    continue;
                }

                foreach ($listenerElement as $callbackElement) {
                    $eventName   = (string) $callbackElement['type'];
                    $methodName  = (string) $callbackElement['method'];

                    $metadata->addEntityListener($eventName, $className, $methodName);
                }
            }
        }
    }

    /**
     * Parses (nested) option elements.
     *
     * @param SimpleXMLElement $options The XML element.
     *
     * @return array The options array.
     */
    private function parseOptions(SimpleXMLElement $options)
    {
        $array = [];

        /* @var $option SimpleXMLElement */
        foreach ($options as $option) {
            if ($option->count()) {
                $value = $this->parseOptions($option->children());
            } else {
                $value = (string) $option;
            }

            $attributes = $option->attributes();

            if (isset($attributes->name)) {
                $nameAttribute = (string) $attributes->name;
                $array[$nameAttribute] = in_array($nameAttribute, ['unsigned', 'fixed'])
                    ? $this->evaluateBoolean($value)
                    : $value;
            } else {
                $array[] = $value;
            }

        }

        return $array;
    }

    /**
     * @param SimpleXMLElement $fieldElement
     * @param string           $fieldName
     * @param bool             $isVersioned
     *
     * @return FieldMetadata
     */
    private function convertFieldElementToFieldMetadata(SimpleXMLElement $fieldElement, string $fieldName, bool $isVersioned)
    {
        $fieldMetadata = $isVersioned
            ? new VersionFieldMetadata($fieldName)
            : new FieldMetadata($fieldName)
        ;

        $fieldMetadata->setType(Type::getType('string'));

        if (isset($fieldElement['type'])) {
            $fieldMetadata->setType(Type::getType((string) $fieldElement['type']));
        }

        if (isset($fieldElement['column'])) {
            $fieldMetadata->setColumnName((string) $fieldElement['column']);
        }

        if (isset($fieldElement['length'])) {
            $fieldMetadata->setLength((int) $fieldElement['length']);
        }

        if (isset($fieldElement['precision'])) {
            $fieldMetadata->setPrecision((int) $fieldElement['precision']);
        }

        if (isset($fieldElement['scale'])) {
            $fieldMetadata->setScale((int) $fieldElement['scale']);
        }

        if (isset($fieldElement['unique'])) {
            $fieldMetadata->setUnique($this->evaluateBoolean($fieldElement['unique']));
        }

        if (isset($fieldElement['nullable'])) {
            $fieldMetadata->setNullable($this->evaluateBoolean($fieldElement['nullable']));
        }

        if (isset($fieldElement['column-definition'])) {
            $fieldMetadata->setColumnDefinition((string) $fieldElement['column-definition']);
        }

        if (isset($fieldElement->options)) {
            $fieldMetadata->setOptions($this->parseOptions($fieldElement->options->children()));
        }

        return $fieldMetadata;
    }

    /**
     * Constructs a joinColumn mapping array based on the information
     * found in the given SimpleXMLElement.
     *
     * @param SimpleXMLElement $joinColumnElement The XML element.
     *
     * @return JoinColumnMetadata
     */
    private function convertJoinColumnElementToJoinColumnMetadata(SimpleXMLElement $joinColumnElement)
    {
        $joinColumnMetadata = new JoinColumnMetadata();

        $joinColumnMetadata->setColumnName((string) $joinColumnElement['name']);
        $joinColumnMetadata->setReferencedColumnName((string) $joinColumnElement['referenced-column-name']);

        if (isset($joinColumnElement['column-definition'])) {
            $joinColumnMetadata->setColumnDefinition((string) $joinColumnElement['column-definition']);
        }

        if (isset($joinColumnElement['field-name'])) {
            $joinColumnMetadata->setAliasedName((string) $joinColumnElement['field-name']);
        }

        if (isset($joinColumnElement['nullable'])) {
            $joinColumnMetadata->setNullable($this->evaluateBoolean($joinColumnElement['nullable']));
        }

        if (isset($joinColumnElement['unique'])) {
            $joinColumnMetadata->setUnique($this->evaluateBoolean($joinColumnElement['unique']));
        }

        if (isset($joinColumnElement['on-delete'])) {
            $joinColumnMetadata->setOnDelete(strtoupper((string) $joinColumnElement['on-delete']));
        }

        return $joinColumnMetadata;
    }

    /**
     * Parse / Normalize the cache configuration
     *
     * @param SimpleXMLElement $cacheMapping
     *
     * @return array
     */
    private function cacheToArray(SimpleXMLElement $cacheMapping)
    {
        $region = isset($cacheMapping['region']) ? (string) $cacheMapping['region'] : null;
        $usage  = isset($cacheMapping['usage']) ? strtoupper($cacheMapping['usage']) : null;

        if ($usage && ! defined('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $usage)) {
            throw new \InvalidArgumentException(sprintf('Invalid cache usage "%s"', $usage));
        }

        if ($usage) {
            $usage = constant('Doctrine\ORM\Mapping\ClassMetadata::CACHE_USAGE_' . $usage);
        }

        return [
            'usage'  => $usage,
            'region' => $region,
        ];
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param SimpleXMLElement $cascadeElement The cascade element.
     *
     * @return array The list of cascade options.
     */
    private function _getCascadeMappings(SimpleXMLElement $cascadeElement)
    {
        $cascades = [];

        /* @var $action SimpleXmlElement */
        foreach ($cascadeElement->children() as $action) {
            // According to the JPA specifications, XML uses "cascade-persist"
            // instead of "persist". Here, both variations are supported
            // because Annotation use "persist" and we want to make sure that
            // this driver doesn't need to know anything about the supported
            // cascading actions
            $cascades[] = str_replace('cascade-', '', $action->getName());
        }

        return $cascades;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = [];
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string) $entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } else if (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = (string) $mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        } else if (isset($xmlElement->embeddable)) {
            foreach ($xmlElement->embeddable as $embeddableElement) {
                $embeddableName = (string) $embeddableElement['name'];
                $result[$embeddableName] = $embeddableElement;
            }
        }

        return $result;
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    protected function evaluateBoolean($element)
    {
        $flag = (string) $element;

        return ($flag == "true" || $flag == "1");
    }
}
