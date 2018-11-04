<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use SimpleXMLElement;
use function array_filter;
use function class_exists;
use function constant;
use function explode;
use function file_get_contents;
use function get_class;
use function in_array;
use function simplexml_load_string;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

/**
 * XmlDriver is a metadata driver that enables mapping through XML files.
 */
class XmlDriver extends FileDriver
{
    public const DEFAULT_FILE_EXTENSION = '.dcm.xml';

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
    public function loadMetadataForClass(
        string $className,
        Mapping\ClassMetadata $metadata,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) {
        /** @var SimpleXMLElement $xmlRoot */
        $xmlRoot = $this->getElement($className);

        if ($xmlRoot->getName() === 'entity') {
            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClassName((string) $xmlRoot['repository-class']);
            }

            if (isset($xmlRoot['read-only']) && $this->evaluateBoolean($xmlRoot['read-only'])) {
                $metadata->asReadOnly();
            }
        } elseif ($xmlRoot->getName() === 'mapped-superclass') {
            if (isset($xmlRoot['repository-class'])) {
                $metadata->setCustomRepositoryClassName((string) $xmlRoot['repository-class']);
            }

            $metadata->isMappedSuperclass = true;
        } elseif ($xmlRoot->getName() === 'embeddable') {
            $metadata->isEmbeddedClass = true;
        } else {
            throw Mapping\MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        // Process table information
        $parent = $metadata->getParent();

        if ($parent && $parent->inheritanceType === Mapping\InheritanceType::SINGLE_TABLE) {
            $metadata->setTable($parent->table);
        } else {
            $namingStrategy = $metadataBuildingContext->getNamingStrategy();
            $tableMetadata  = new Mapping\TableMetadata();

            $tableMetadata->setName($namingStrategy->classToTableName($metadata->getClassName()));

            // Evaluate <entity...> attributes
            if (isset($xmlRoot['table'])) {
                $tableMetadata->setName((string) $xmlRoot['table']);
            }

            if (isset($xmlRoot['schema'])) {
                $tableMetadata->setSchema((string) $xmlRoot['schema']);
            }

            if (isset($xmlRoot->options)) {
                $options = $this->parseOptions($xmlRoot->options->children());

                foreach ($options as $optionName => $optionValue) {
                    $tableMetadata->addOption($optionName, $optionValue);
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

                    $tableMetadata->addIndex([
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

                    $tableMetadata->addUniqueConstraint([
                        'name'    => $indexName,
                        'columns' => $columns,
                        'options' => $options,
                        'flags'   => $flags,
                    ]);
                }
            }

            $metadata->setTable($tableMetadata);
        }

        // Evaluate second level cache
        if (isset($xmlRoot->cache)) {
            $cache = $this->convertCacheElementToCacheMetadata($xmlRoot->cache, $metadata);

            $metadata->setCache($cache);
        }

        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = strtoupper((string) $xmlRoot['inheritance-type']);

            $metadata->setInheritanceType(
                constant(sprintf('%s::%s', Mapping\InheritanceType::class, $inheritanceType))
            );

            if ($metadata->inheritanceType !== Mapping\InheritanceType::NONE) {
                $discriminatorColumn = new Mapping\DiscriminatorColumnMetadata();

                $discriminatorColumn->setTableName($metadata->getTableName());
                $discriminatorColumn->setColumnName('dtype');
                $discriminatorColumn->setType(Type::getType('string'));
                $discriminatorColumn->setLength(255);

                // Evaluate <discriminator-column...>
                if (isset($xmlRoot->{'discriminator-column'})) {
                    $discriminatorColumnMapping = $xmlRoot->{'discriminator-column'};
                    $typeName                   = (string) ($discriminatorColumnMapping['type'] ?? 'string');

                    $discriminatorColumn->setType(Type::getType($typeName));
                    $discriminatorColumn->setColumnName((string) $discriminatorColumnMapping['name']);

                    if (isset($discriminatorColumnMapping['column-definition'])) {
                        $discriminatorColumn->setColumnDefinition((string) $discriminatorColumnMapping['column-definition']);
                    }

                    if (isset($discriminatorColumnMapping['length'])) {
                        $discriminatorColumn->setLength((int) $discriminatorColumnMapping['length']);
                    }
                }

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
                constant(sprintf('%s::%s', Mapping\ChangeTrackingPolicy::class, $changeTrackingPolicy))
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
                    'columnPrefix' => $useColumnPrefix ? $columnPrefix : false,
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
                $strategy = (string) ($idElement->generator['strategy'] ?? 'AUTO');

                $idGeneratorType = constant(sprintf('%s::%s', Mapping\GeneratorType::class, strtoupper($strategy)));

                if ($idGeneratorType !== Mapping\GeneratorType::NONE) {
                    $idGeneratorDefinition = [];

                    // Check for SequenceGenerator/TableGenerator definition
                    if (isset($idElement->{'sequence-generator'})) {
                        $seqGenerator          = $idElement->{'sequence-generator'};
                        $idGeneratorDefinition = [
                            'sequenceName' => (string) $seqGenerator['sequence-name'],
                            'allocationSize' => (string) $seqGenerator['allocation-size'],
                        ];
                    } elseif (isset($idElement->{'custom-id-generator'})) {
                        $customGenerator = $idElement->{'custom-id-generator'};

                        $idGeneratorDefinition = [
                            'class' => (string) $customGenerator['class'],
                            'arguments' => [],
                        ];
                    } elseif (isset($idElement->{'table-generator'})) {
                        throw Mapping\MappingException::tableIdGeneratorNotImplemented($className);
                    }

                    $fieldMetadata->setValueGenerator(new Mapping\ValueGeneratorMetadata($idGeneratorType, $idGeneratorDefinition));
                }
            }

            $metadata->addProperty($fieldMetadata);
        }

        // Evaluate <one-to-one ...> mappings
        if (isset($xmlRoot->{'one-to-one'})) {
            foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                $association  = new Mapping\OneToOneAssociationMetadata((string) $oneToOneElement['field']);
                $targetEntity = (string) $oneToOneElement['target-entity'];

                $association->setTargetEntity($targetEntity);

                if (isset($associationIds[$association->getName()])) {
                    $association->setPrimaryKey(true);
                }

                if (isset($oneToOneElement['fetch'])) {
                    $association->setFetchMode(
                        constant(sprintf('%s::%s', Mapping\FetchMode::class, (string) $oneToOneElement['fetch']))
                    );
                }

                if (isset($oneToOneElement['mapped-by'])) {
                    $association->setMappedBy((string) $oneToOneElement['mapped-by']);
                    $association->setOwningSide(false);
                } else {
                    if (isset($oneToOneElement['inversed-by'])) {
                        $association->setInversedBy((string) $oneToOneElement['inversed-by']);
                    }

                    $joinColumns = [];

                    if (isset($oneToOneElement->{'join-column'})) {
                        $joinColumns[] = $this->convertJoinColumnElementToJoinColumnMetadata($oneToOneElement->{'join-column'});
                    } elseif (isset($oneToOneElement->{'join-columns'})) {
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
                $association  = new Mapping\OneToManyAssociationMetadata((string) $oneToManyElement['field']);
                $targetEntity = (string) $oneToManyElement['target-entity'];

                $association->setTargetEntity($targetEntity);
                $association->setOwningSide(false);
                $association->setMappedBy((string) $oneToManyElement['mapped-by']);

                if (isset($associationIds[$association->getName()])) {
                    throw Mapping\MappingException::illegalToManyIdentifierAssociation($className, $association->getName());
                }

                if (isset($oneToManyElement['fetch'])) {
                    $association->setFetchMode(
                        constant(sprintf('%s::%s', Mapping\FetchMode::class, (string) $oneToManyElement['fetch']))
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
                } elseif (isset($oneToManyElement->{'index-by'})) {
                    throw new InvalidArgumentException('<index-by /> is not a valid tag');
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
                $association  = new Mapping\ManyToOneAssociationMetadata((string) $manyToOneElement['field']);
                $targetEntity = (string) $manyToOneElement['target-entity'];

                $association->setTargetEntity($targetEntity);

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
                } elseif (isset($manyToOneElement->{'join-columns'})) {
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
                $association  = new Mapping\ManyToManyAssociationMetadata((string) $manyToManyElement['field']);
                $targetEntity = (string) $manyToManyElement['target-entity'];

                $association->setTargetEntity($targetEntity);

                if (isset($associationIds[$association->getName()])) {
                    throw Mapping\MappingException::illegalToManyIdentifierAssociation($className, $association->getName());
                }

                if (isset($manyToManyElement['fetch'])) {
                    $association->setFetchMode(
                        constant(sprintf('%s::%s', Mapping\FetchMode::class, (string) $manyToManyElement['fetch']))
                    );
                }

                if (isset($manyToManyElement['orphan-removal'])) {
                    $association->setOrphanRemoval($this->evaluateBoolean($manyToManyElement['orphan-removal']));
                }

                if (isset($manyToManyElement['mapped-by'])) {
                    $association->setMappedBy((string) $manyToManyElement['mapped-by']);
                    $association->setOwningSide(false);
                } elseif (isset($manyToManyElement->{'join-table'})) {
                    if (isset($manyToManyElement['inversed-by'])) {
                        $association->setInversedBy((string) $manyToManyElement['inversed-by']);
                    }

                    $joinTableElement = $manyToManyElement->{'join-table'};
                    $joinTable        = new Mapping\JoinTableMetadata();

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
                } elseif (isset($manyToManyElement->{'index-by'})) {
                    throw new InvalidArgumentException('<index-by /> is not a valid tag');
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
                    throw Mapping\MappingException::invalidOverrideFieldName($metadata->getClassName(), $fieldName);
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
                    $joinTableElement = $overrideElement->{'join-table'};
                    $joinTable        = new Mapping\JoinTableMetadata();

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
                $eventName  = constant(Events::class . '::' . (string) $lifecycleCallback['type']);
                $methodName = (string) $lifecycleCallback['method'];

                $metadata->addLifecycleCallback($methodName, $eventName);
            }
        }

        // Evaluate entity listener
        if (isset($xmlRoot->{'entity-listeners'})) {
            foreach ($xmlRoot->{'entity-listeners'}->{'entity-listener'} as $listenerElement) {
                $listenerClassName = (string) $listenerElement['class'];

                if (! class_exists($listenerClassName)) {
                    throw Mapping\MappingException::entityListenerClassNotFound(
                        $listenerClassName,
                        $metadata->getClassName()
                    );
                }

                $listenerClass = new ReflectionClass($listenerClassName);

                // Evaluate the listener using naming convention.
                if ($listenerElement->count() === 0) {
                    /** @var ReflectionMethod $method */
                    foreach ($listenerClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                        foreach ($this->getMethodCallbacks($method) as $callback) {
                            $metadata->addEntityListener($callback, $listenerClassName, $method->getName());
                        }
                    }

                    continue;
                }

                foreach ($listenerElement as $callbackElement) {
                    $eventName  = (string) $callbackElement['type'];
                    $methodName = (string) $callbackElement['method'];

                    $metadata->addEntityListener($eventName, $listenerClassName, $methodName);
                }
            }
        }
    }

    /**
     * Parses (nested) option elements.
     *
     * @param SimpleXMLElement $options The XML element.
     *
     * @return mixed[] The options array.
     */
    private function parseOptions(SimpleXMLElement $options)
    {
        $array = [];

        /** @var SimpleXMLElement $option */
        foreach ($options as $option) {
            if ($option->count()) {
                $value = $this->parseOptions($option->children());
            } else {
                $value = (string) $option;
            }

            $attributes = $option->attributes();

            if (isset($attributes->name)) {
                $nameAttribute         = (string) $attributes->name;
                $array[$nameAttribute] = in_array($nameAttribute, ['unsigned', 'fixed'], true)
                    ? $this->evaluateBoolean($value)
                    : $value;
            } else {
                $array[] = $value;
            }
        }

        return $array;
    }

    /**
     * @return Mapping\FieldMetadata
     */
    private function convertFieldElementToFieldMetadata(SimpleXMLElement $fieldElement, string $fieldName, bool $isVersioned)
    {
        $fieldMetadata = $isVersioned
            ? new Mapping\VersionFieldMetadata($fieldName)
            : new Mapping\FieldMetadata($fieldName);

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
     * @return Mapping\JoinColumnMetadata
     */
    private function convertJoinColumnElementToJoinColumnMetadata(SimpleXMLElement $joinColumnElement)
    {
        $joinColumnMetadata = new Mapping\JoinColumnMetadata();

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
     * @param string|null $fieldName
     *
     * @return Mapping\CacheMetadata
     */
    private function convertCacheElementToCacheMetadata(
        SimpleXMLElement $cacheMapping,
        Mapping\ClassMetadata $metadata,
        $fieldName = null
    ) {
        $baseRegion    = strtolower(str_replace('\\', '_', $metadata->getRootClassName()));
        $defaultRegion = $baseRegion . ($fieldName ? '__' . $fieldName : '');

        $region = (string) ($cacheMapping['region'] ?? $defaultRegion);
        $usage  = isset($cacheMapping['usage'])
            ? constant(sprintf('%s::%s', Mapping\CacheUsage::class, strtoupper((string) $cacheMapping['usage'])))
            : Mapping\CacheUsage::READ_ONLY;

        return new Mapping\CacheMetadata($usage, $region);
    }

    /**
     * Parses the given method.
     *
     * @return string[]
     */
    private function getMethodCallbacks(ReflectionMethod $method)
    {
        $events = [
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::preRemove,
            Events::postRemove,
            Events::postLoad,
            Events::preFlush,
        ];

        return array_filter($events, static function ($eventName) use ($method) {
            return $eventName === $method->getName();
        });
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param SimpleXMLElement $cascadeElement The cascade element.
     *
     * @return string[] The list of cascade options.
     */
    private function getCascadeMappings(SimpleXMLElement $cascadeElement)
    {
        $cascades = [];

        /** @var SimpleXMLElement $action */
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
        // Note: we do not use `simplexml_load_file()` because of https://bugs.php.net/bug.php?id=62577
        $xmlElement = simplexml_load_string(file_get_contents($file));

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName          = (string) $entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className          = (string) $mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        } elseif (isset($xmlElement->embeddable)) {
            foreach ($xmlElement->embeddable as $embeddableElement) {
                $embeddableName          = (string) $embeddableElement['name'];
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

        return $flag === 'true' || $flag === '1';
    }
}
