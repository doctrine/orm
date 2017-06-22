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

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EntityListenerBuilder;
use Doctrine\ORM\Mapping\Builder\CacheMetadataBuilder;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\CacheUsage;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\GeneratorType;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
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
                $metadata->asReadOnly();
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
        if (isset($xmlRoot['table'])) {
            $metadata->table->setName((string) $xmlRoot['table']);
        }

        if (isset($xmlRoot['schema'])) {
            $metadata->table->setSchema((string) $xmlRoot['schema']);
        }

        if (isset($xmlRoot->options)) {
            $options = $this->parseOptions($xmlRoot->options->children());

            foreach ($options as $optionName => $optionValue) {
                $metadata->table->addOption($optionName, $optionValue);
            }
        }

        // Evaluate <indexes...>
        if (isset($xmlRoot->indexes)) {
            foreach ($xmlRoot->indexes->index as $indexXml) {
                $indexName = isset($indexXml['name']) ? (string) $indexXml['name'] : null;
                $columns   = explode(',', (string) $indexXml['columns']);
                $isUnique  = isset($indexXml['unique']) && $indexXml['unique'];
                $options   = isset($indexXml->options) ? $this->parseOptions($indexXml->options->children()) : [];
                $flags     = isset($indexXml['flags']) ? explode(',', (string) $indexXml['flags']) : [];

                $metadata->table->addIndex([
                    'name'    => $indexName,
                    'columns' => $columns,
                    'unique'  => $isUnique,
                    'options' => $options,
                    'flags'   => $flags,
                ]);
            }
        }

        // Evaluate <unique-constraints..>

        if (isset($xmlRoot->{'unique-constraints'})) {
            foreach ($xmlRoot->{'unique-constraints'}->{'unique-constraint'} as $uniqueXml) {
                $indexName = isset($uniqueXml['name']) ? (string) $uniqueXml['name'] : null;
                $columns   = explode(',', (string) $uniqueXml['columns']);
                $options   = isset($uniqueXml->options) ? $this->parseOptions($uniqueXml->options->children()) : [];
                $flags     = isset($uniqueXml['flags']) ? explode(',', (string) $uniqueXml['flags']) : [];

                $metadata->table->addUniqueConstraint([
                    'name'    => $indexName,
                    'columns' => $columns,
                    'options' => $options,
                    'flags'   => $flags,
                ]);
            }
        }

        // Evaluate second level cache
        if (isset($xmlRoot->cache)) {
            $cache = $this->convertCacheElementToCacheMetadata($xmlRoot->cache, $metadata);

            $metadata->setCache($cache);
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
                constant(sprintf('%s::%s', InheritanceType::class, $inheritanceType))
            );

            if ($metadata->inheritanceType !== InheritanceType::NONE) {
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
                constant(sprintf('%s::%s', ChangeTrackingPolicy::class, $changeTrackingPolicy))
            );
        }

        // Evaluate <field ...> mappings
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $fieldElement) {
                $fieldName        = (string) $fieldElement['name'];
                $isFieldVersioned = isset($fieldElement['version']) && $fieldElement['version'];
                $fieldMetadata    = $this->convertFieldElementToFieldMetadata($fieldElement, $fieldName, $isFieldVersioned);

                $metadata->addProperty($fieldMetadata);
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

                $metadata->setIdGeneratorType(constant(sprintf('%s::%s', GeneratorType::class, strtoupper($strategy))));
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
                $association = new OneToOneAssociationMetadata((string) $oneToOneElement['field']);

                $association->setTargetEntity((string) $oneToOneElement['target-entity']);

                if (isset($associationIds[$association->getName()])) {
                    $association->setPrimaryKey(true);
                }

                if (isset($oneToOneElement['fetch'])) {
                    $association->setFetchMode(
                        constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $oneToOneElement['fetch'])
                    );
                }

                if (isset($oneToOneElement['mapped-by'])) {
                    $association->setMappedBy((string) $oneToOneElement['mapped-by']);
                } else {
                    if (isset($oneToOneElement['inversed-by'])) {
                        $association->setInversedBy((string) $oneToOneElement['inversed-by']);
                    }

                    $joinColumns = [];

                    if (isset($oneToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($oneToOneElement->{'join-column'});
                    } else if (isset($oneToOneElement->{'join-columns'})) {
                        foreach ($oneToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                        }
                    }

                    $association->setJoinColumns($joinColumns);
                }

                if (isset($oneToOneElement->cascade)) {
                    $association->setCascade($this->getCascadeMappings($oneToOneElement->cascade));
                }

                if (isset($oneToOneElement['orphan-removal'])) {
                    $association->setOrphanRemoval($this->evaluateBoolean($oneToOneElement['orphan-removal']));
                }

                // Evaluate second level cache
                if (isset($oneToOneElement->cache)) {
                    $association->setCache(
                        $this->convertCacheElementToCacheMetadata(
                            $oneToOneElement->cache,
                            $metadata,
                            $association->getName()
                        )
                    );
                }

                $metadata->addProperty($association);
            }
        }

        // Evaluate <one-to-many ...> mappings
        if (isset($xmlRoot->{'one-to-many'})) {
            foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                $association = new OneToManyAssociationMetadata((string) $oneToManyElement['field']);

                $association->setTargetEntity((string) $oneToManyElement['target-entity']);
                $association->setMappedBy((string) $oneToManyElement['mapped-by']);

                if (isset($oneToManyElement['fetch'])) {
                    $association->setFetchMode(
                        constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $oneToManyElement['fetch'])
                    );
                }

                if (isset($oneToManyElement->cascade)) {
                    $association->setCascade($this->getCascadeMappings($oneToManyElement->cascade));
                }

                if (isset($oneToManyElement['orphan-removal'])) {
                    $association->setOrphanRemoval($this->evaluateBoolean($oneToManyElement['orphan-removal']));
                }

                if (isset($oneToManyElement->{'order-by'})) {
                    $orderBy = [];

                    foreach ($oneToManyElement->{'order-by'}->{'order-by-field'} as $orderByField) {
                        $orderBy[(string) $orderByField['name']] = (string) $orderByField['direction'];
                    }

                    $association->setOrderBy($orderBy);
                }

                if (isset($oneToManyElement['index-by'])) {
                    $association->setIndexedBy((string) $oneToManyElement['index-by']);
                } else if (isset($oneToManyElement->{'index-by'})) {
                    throw new \InvalidArgumentException("<index-by /> is not a valid tag");
                }

                // Evaluate second level cache
                if (isset($oneToManyElement->cache)) {
                    $association->setCache(
                        $this->convertCacheElementToCacheMetadata(
                            $oneToManyElement->cache,
                            $metadata,
                            $association->getName()
                        )
                    );
                }

                $metadata->addProperty($association);
            }
        }

        // Evaluate <many-to-one ...> mappings
        if (isset($xmlRoot->{'many-to-one'})) {
            foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                $association = new ManyToOneAssociationMetadata((string) $manyToOneElement['field']);

                $association->setTargetEntity((string) $manyToOneElement['target-entity']);

                if (isset($associationIds[$association->getName()])) {
                    $association->setPrimaryKey(true);
                }

                if (isset($manyToOneElement['fetch'])) {
                    $association->setFetchMode(
                        constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $manyToOneElement['fetch'])
                    );
                }

                if (isset($manyToOneElement['inversed-by'])) {
                    $association->setInversedBy((string) $manyToOneElement['inversed-by']);
                }

                $joinColumns = [];

                if (isset($manyToOneElement->{'join-column'})) {
                    $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($manyToOneElement->{'join-column'});
                } else if (isset($manyToOneElement->{'join-columns'})) {
                    foreach ($manyToOneElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }
                }

                $association->setJoinColumns($joinColumns);

                if (isset($manyToOneElement->cascade)) {
                    $association->setCascade($this->getCascadeMappings($manyToOneElement->cascade));
                }

                // Evaluate second level cache
                if (isset($manyToOneElement->cache)) {
                    $association->setCache(
                        $this->convertCacheElementToCacheMetadata(
                            $manyToOneElement->cache,
                            $metadata,
                            $association->getName()
                        )
                    );
                }

                $metadata->addProperty($association);
            }
        }

        // Evaluate <many-to-many ...> mappings
        if (isset($xmlRoot->{'many-to-many'})) {
            foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                $association = new ManyToManyAssociationMetadata((string) $manyToManyElement['field']);

                $association->setTargetEntity((string) $manyToManyElement['target-entity']);

                if (isset($manyToManyElement['fetch'])) {
                    $association->setFetchMode(
                        constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $manyToManyElement['fetch'])
                    );
                }

                if (isset($manyToManyElement['orphan-removal'])) {
                    $association->setOrphanRemoval($this->evaluateBoolean($manyToManyElement['orphan-removal']));
                }

                if (isset($manyToManyElement['mapped-by'])) {
                    $association->setMappedBy((string) $manyToManyElement['mapped-by']);
                } else if (isset($manyToManyElement->{'join-table'})) {
                    if (isset($manyToManyElement['inversed-by'])) {
                        $association->setInversedBy((string) $manyToManyElement['inversed-by']);
                    }

                    $joinTableElement = $manyToManyElement->{'join-table'};
                    $joinTable        = new JoinTableMetadata();

                    if (isset($joinTableElement['name'])) {
                        $joinTable->setName((string) $joinTableElement['name']);
                    }

                    if (isset($joinTableElement['schema'])) {
                        $joinTable->setSchema((string) $joinTableElement['schema']);
                    }

                    if (isset($joinTableElement->{'join-columns'})) {
                        foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumn = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);

                            $joinTable->addJoinColumn($joinColumn);
                        }
                    }

                    if (isset($joinTableElement->{'inverse-join-columns'})) {
                        foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumn = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);

                            $joinTable->addInverseJoinColumn($joinColumn);
                        }
                    }

                    $association->setJoinTable($joinTable);
                }

                if (isset($manyToManyElement->cascade)) {
                    $association->setCascade($this->getCascadeMappings($manyToManyElement->cascade));
                }

                if (isset($manyToManyElement->{'order-by'})) {
                    $orderBy = [];

                    foreach ($manyToManyElement->{'order-by'}->{'order-by-field'} as $orderByField) {
                        $orderBy[(string) $orderByField['name']] = (string) $orderByField['direction'];
                    }

                    $association->setOrderBy($orderBy);
                }

                if (isset($manyToManyElement['index-by'])) {
                    $association->setIndexedBy((string) $manyToManyElement['index-by']);
                } else if (isset($manyToManyElement->{'index-by'})) {
                    throw new \InvalidArgumentException("<index-by /> is not a valid tag");
                }

                // Evaluate second level cache
                if (isset($manyToManyElement->cache)) {
                    $association->setCache(
                        $this->convertCacheElementToCacheMetadata(
                            $manyToManyElement->cache,
                            $metadata,
                            $association->getName()
                        )
                    );
                }

                $metadata->addProperty($association);
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'attribute-overrides'})) {
            foreach ($xmlRoot->{'attribute-overrides'}->{'attribute-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];

                foreach ($overrideElement->field as $fieldElement) {
                    $fieldMetadata = $this->convertFieldElementToFieldMetadata($fieldElement, $fieldName, false);

                    $metadata->setPropertyOverride($fieldMetadata);
                }
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'association-overrides'})) {
            foreach ($xmlRoot->{'association-overrides'}->{'association-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];
                $property  = $metadata->getProperty($fieldName);

                if (! $property) {
                    throw MappingException::invalidOverrideFieldName($metadata->name, $fieldName);
                }

                $existingClass = get_class($property);
                $override      = new $existingClass($fieldName);

                // Check for join-columns
                if (isset($overrideElement->{'join-columns'})) {
                    $joinColumns = [];

                    foreach ($overrideElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);
                    }

                    $override->setJoinColumns($joinColumns);
                }

                // Check for join-table
                if ($overrideElement->{'join-table'}) {
                    $joinTableElement   = $overrideElement->{'join-table'};
                    $joinTable          = new JoinTableMetadata();

                    if (isset($joinTableElement['name'])) {
                        $joinTable->setName((string) $joinTableElement['name']);
                    }

                    if (isset($joinTableElement['schema'])) {
                        $joinTable->setSchema((string) $joinTableElement['schema']);
                    }

                    if (isset($joinTableElement->{'join-columns'})) {
                        foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumn = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);

                            $joinTable->addJoinColumn($joinColumn);
                        }
                    }

                    if (isset($joinTableElement->{'inverse-join-columns'})) {
                        foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                            $joinColumn = $this->convertJoinColumnElementToJoinColumnMetadata($joinColumnElement);

                            $joinTable->addInverseJoinColumn($joinColumn);
                        }
                    }

                    $override->setJoinTable($joinTable);
                }

                // Check for inversed-by
                if (isset($overrideElement->{'inversed-by'})) {
                    $override->setInversedBy((string) $overrideElement->{'inversed-by'}['name']);
                }

                // Check for fetch
                if (isset($overrideElement['fetch'])) {
                    $override->setFetchMode(
                        constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $overrideElement['fetch'])
                    );
                }

                $metadata->setPropertyOverride($override);
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
     * Parse the given Cache as CacheMetadata
     *
     * @param \SimpleXMLElement $cacheMapping
     * @param ClassMetadata     $metadata
     * @param null|string       $fieldName
     *
     * @return CacheMetadata
     */
    private function convertCacheElementToCacheMetadata(SimpleXMLElement $cacheMapping, ClassMetadata $metadata, $fieldName = null)
    {
        $baseRegion    = strtolower(str_replace('\\', '_', $metadata->rootEntityName));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');
        $cacheBuilder  = new CacheMetadataBuilder();

        $region = isset($cacheMapping['region']) ? (string) $cacheMapping['region'] : $defaultRegion;
        $usage  = isset($cacheMapping['usage'])
            ? constant(sprintf('%s::%s', CacheUsage::class, strtoupper($cacheMapping['usage'])))
            : CacheUsage::READ_ONLY
        ;

        $cacheBuilder
            ->withUsage($usage)
            ->withRegion($region)
        ;

        return $cacheBuilder->build();
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

        if ($usage) {
            $usage = constant(sprintf('%s::%s', CacheUsage::class, $usage));
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
    private function getCascadeMappings(SimpleXMLElement $cascadeElement)
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
