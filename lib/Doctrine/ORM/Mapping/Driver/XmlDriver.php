<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Annotation;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\Builder;
use InvalidArgumentException;
use SimpleXMLElement;
use function class_exists;
use function constant;
use function explode;
use function file_get_contents;
use function in_array;
use function simplexml_load_string;
use function sprintf;
use function str_replace;
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
     *
     * @throws DBALException
     */
    public function loadMetadataForClass(
        string $className,
        ?Mapping\ComponentMetadata $parent,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : Mapping\ComponentMetadata {
        $metadata = new Mapping\ClassMetadata($className, $parent);

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
            // Handle the case where a middle mapped super class inherits from a single table inheritance tree.
            do {
                if (! $parent->isMappedSuperclass) {
                    $metadata->setTable($parent->table);

                    break;
                }

                $parent = $parent->getParent();
            } while ($parent !== null);
        } else {
            $tableAnnotation = new Annotation\Table();

            // Evaluate <entity...> attributes
            if (isset($xmlRoot['table'])) {
                $tableAnnotation->name = (string) $xmlRoot['table'];
            }

            if (isset($xmlRoot['schema'])) {
                $tableAnnotation->schema = (string) $xmlRoot['schema'];
            }

            // Evaluate <indexes...>
            if (isset($xmlRoot->indexes)) {
                $tableAnnotation->indexes = $this->parseIndexes($xmlRoot->indexes->children());
            }

            // Evaluate <unique-constraints..>
            if (isset($xmlRoot->{'unique-constraints'})) {
                $tableAnnotation->uniqueConstraints = $this->parseUniqueConstraints($xmlRoot->{'unique-constraints'}->children());
            }

            if (isset($xmlRoot->options)) {
                $tableAnnotation->options = $this->parseOptions($xmlRoot->options->children());
            }

            $tableBuilder = new Builder\TableMetadataBuilder($metadataBuildingContext);

            $tableBuilder
                ->withEntityClassMetadata($metadata)
                ->withTableAnnotation($tableAnnotation);

            $metadata->setTable($tableBuilder->build());
        }

        // Evaluate second level cache
        if (isset($xmlRoot->cache)) {
            $cacheBuilder = new Builder\CacheMetadataBuilder($metadataBuildingContext);

            $cacheBuilder
                ->withComponentMetadata($metadata)
                ->withCacheAnnotation($this->convertCacheElementToCacheAnnotation($xmlRoot->cache));

            $metadata->setCache($cacheBuilder->build());
        }

        if (isset($xmlRoot['inheritance-type'])) {
            $inheritanceType = strtoupper((string) $xmlRoot['inheritance-type']);

            $metadata->setInheritanceType(
                constant(sprintf('%s::%s', Mapping\InheritanceType::class, $inheritanceType))
            );

            if ($metadata->inheritanceType !== Mapping\InheritanceType::NONE) {
                $discriminatorColumnBuilder = new Builder\DiscriminatorColumnMetadataBuilder($metadataBuildingContext);

                $discriminatorColumnBuilder
                    ->withComponentMetadata($metadata)
                    ->withDiscriminatorColumnAnnotation(
                        isset($xmlRoot->{'discriminator-column'})
                            ? $this->convertDiscrimininatorColumnElementToDiscriminatorColumnAnnotation($xmlRoot->{'discriminator-column'})
                            : null
                    );

                $metadata->setDiscriminatorColumn($discriminatorColumnBuilder->build());

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
            $fieldBuilder = new Builder\FieldMetadataBuilder($metadataBuildingContext);

            $fieldBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->field as $fieldElement) {
                $versionAnnotation = isset($fieldElement['version']) && $this->evaluateBoolean($fieldElement['version'])
                    ? new Annotation\Version()
                    : null;

                $fieldBuilder
                    ->withFieldName((string) $fieldElement['name'])
                    ->withColumnAnnotation($this->convertFieldElementToColumnAnnotation($fieldElement))
                    ->withIdAnnotation(null)
                    ->withVersionAnnotation($versionAnnotation);

                $fieldMetadata = $fieldBuilder->build();

                // Prevent column duplication
                if ($metadata->checkPropertyDuplication($fieldMetadata->getColumnName())) {
                    throw Mapping\MappingException::duplicateColumnName(
                        $metadata->getClassName(),
                        $fieldMetadata->getColumnName()
                    );
                }

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
                    'fieldName'    => (string) $embeddedMapping['name'],
                    'class'        => (string) $embeddedMapping['class'],
                    'columnPrefix' => $useColumnPrefix ? $columnPrefix : false,
                ];

                $metadata->mapEmbedded($mapping);
            }
        }

        // Evaluate <id ...> mappings
        $associationIds = [];

        $fieldBuilder = new Builder\FieldMetadataBuilder($metadataBuildingContext);

        $fieldBuilder
            ->withComponentMetadata($metadata);

        foreach ($xmlRoot->id as $idElement) {
            $fieldName = (string) $idElement['name'];

            if (isset($idElement['association-key']) && $this->evaluateBoolean($idElement['association-key'])) {
                $associationIds[$fieldName] = true;

                continue;
            }

            $versionAnnotation = isset($idElement['version']) && $this->evaluateBoolean($idElement['version'])
                ? new Annotation\Version()
                : null;

            $fieldMetadata = $fieldBuilder
                ->withFieldName($fieldName)
                ->withColumnAnnotation($this->convertFieldElementToColumnAnnotation($idElement))
                ->withIdAnnotation(new Annotation\Id())
                ->withVersionAnnotation($versionAnnotation)
                ->withGeneratedValueAnnotation(
                    isset($idElement->generator)
                        ? $this->convertGeneratorElementToGeneratedValueAnnotation($idElement->generator)
                        : null
                )
                ->withSequenceGeneratorAnnotation(
                    isset($idElement->{'sequence-generator'})
                        ? $this->convertSequenceGeneratorElementToSequenceGeneratorAnnotation($idElement->{'sequence-generator'})
                        : null
                )
                ->withCustomIdGeneratorAnnotation(
                    isset($idElement->{'custom-id-generator'})
                        ? $this->convertCustomIdGeneratorElementToCustomIdGeneratorAnnotation($idElement->{'custom-id-generator'})
                        : null
                )
                ->build();

            // Prevent column duplication
            if ($metadata->checkPropertyDuplication($fieldMetadata->getColumnName())) {
                throw Mapping\MappingException::duplicateColumnName(
                    $metadata->getClassName(),
                    $fieldMetadata->getColumnName()
                );
            }

            $metadata->fieldNames[$fieldMetadata->getColumnName()] = $fieldMetadata->getName();

            $metadata->addProperty($fieldMetadata);
        }

        // Evaluate <one-to-one ...> mappings
        if (isset($xmlRoot->{'one-to-one'})) {
            $oneToOneAssociationBuilder = new Builder\OneToOneAssociationMetadataBuilder($metadataBuildingContext);

            $oneToOneAssociationBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                $fieldName           = (string) $oneToOneElement['field'];
                $associationMetadata = $oneToOneAssociationBuilder
                    ->withFieldName($fieldName)
                    ->withCacheAnnotation(
                        isset($oneToOneElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($oneToOneElement->cache)
                            : null
                    )
                    ->withOneToOneAnnotation($this->convertOneToOneElementToOneToOneAnnotation($oneToOneElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withJoinColumnAnnotation(
                        isset($oneToOneElement->{'join-column'})
                            ? $this->convertJoinColumnElementToJoinColumnAnnotation($oneToOneElement->{'join-column'})
                            : null
                    )
                    ->withJoinColumnsAnnotation(
                        isset($oneToOneElement->{'join-columns'})
                            ? $this->convertJoinColumnsElementToJoinColumnsAnnotation($oneToOneElement->{'join-columns'})
                            : null
                    )
                    ->build();

                // Prevent column duplication
                foreach ($associationMetadata->getJoinColumns() as $joinColumnMetadata) {
                    $columnName = $joinColumnMetadata->getColumnName();

                    // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                    //if ($metadata->checkPropertyDuplication($columnName)) {
                    //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                    //}

                    if ($associationMetadata->isOwningSide()) {
                        $metadata->fieldNames[$columnName] = $associationMetadata->getName();
                    }
                }

                $metadata->addProperty($associationMetadata);
            }
        }

        // Evaluate <many-to-one ...> mappings
        if (isset($xmlRoot->{'many-to-one'})) {
            $manyToOneAssociationBuilder = new Builder\ManyToOneAssociationMetadataBuilder($metadataBuildingContext);

            $manyToOneAssociationBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                $fieldName           = (string) $manyToOneElement['field'];
                $associationMetadata = $manyToOneAssociationBuilder
                    ->withFieldName($fieldName)
                    ->withCacheAnnotation(
                        isset($manyToOneElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($manyToOneElement->cache)
                            : null
                    )
                    ->withManyToOneAnnotation($this->convertManyToOneElementToManyToOneAnnotation($manyToOneElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withJoinColumnAnnotation(
                        isset($manyToOneElement->{'join-column'})
                            ? $this->convertJoinColumnElementToJoinColumnAnnotation($manyToOneElement->{'join-column'})
                            : null
                    )
                    ->withJoinColumnsAnnotation(
                        isset($manyToOneElement->{'join-columns'})
                            ? $this->convertJoinColumnsElementToJoinColumnsAnnotation($manyToOneElement->{'join-columns'})
                            : null
                    )
                    ->build();

                // Prevent column duplication
                foreach ($associationMetadata->getJoinColumns() as $joinColumnMetadata) {
                    $columnName = $joinColumnMetadata->getColumnName();

                    // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                    //if ($metadata->checkPropertyDuplication($columnName)) {
                    //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                    //}

                    if ($associationMetadata->isOwningSide()) {
                        $metadata->fieldNames[$columnName] = $associationMetadata->getName();
                    }
                }

                $metadata->addProperty($associationMetadata);
            }
        }

        // Evaluate <one-to-many ...> mappings
        if (isset($xmlRoot->{'one-to-many'})) {
            $oneToManyAssociationBuilder = new Builder\OneToManyAssociationMetadataBuilder($metadataBuildingContext);

            $oneToManyAssociationBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                $fieldName           = (string) $oneToManyElement['field'];
                $associationMetadata = $oneToManyAssociationBuilder
                    ->withFieldName($fieldName)
                    ->withCacheAnnotation(
                        isset($oneToManyElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($oneToManyElement->cache)
                            : null
                    )
                    ->withOneToManyAnnotation($this->convertOneToManyElementToOneToManyAnnotation($oneToManyElement))
                    ->withOrderByAnnotation(
                        isset($oneToManyElement->{'order-by'})
                            ? $this->convertOrderByElementToOrderByAnnotation($oneToManyElement->{'order-by'})
                            : null
                    )
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->build();

                $metadata->addProperty($associationMetadata);
            }
        }

        // Evaluate <many-to-many ...> mappings
        if (isset($xmlRoot->{'many-to-many'})) {
            $manyToManyAssociationBuilder = new Builder\ManyToManyAssociationMetadataBuilder($metadataBuildingContext);

            $manyToManyAssociationBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                $fieldName           = (string) $manyToManyElement['field'];
                $associationMetadata = $manyToManyAssociationBuilder
                    ->withFieldName($fieldName)
                    ->withCacheAnnotation(
                        isset($manyToManyElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($manyToManyElement->cache)
                            : null
                    )
                    ->withManyToManyAnnotation($this->convertManyToManyElementToManyToManyAnnotation($manyToManyElement))
                    ->withJoinTableAnnotation(
                        isset($manyToManyElement->{'join-table'})
                            ? $this->convertJoinTableElementToJoinTableAnnotation($manyToManyElement->{'join-table'})
                            : null
                    )
                    ->withOrderByAnnotation(
                        isset($manyToManyElement->{'order-by'})
                            ? $this->convertOrderByElementToOrderByAnnotation($manyToManyElement->{'order-by'})
                            : null
                    )
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->build();

                $metadata->addProperty($associationMetadata);
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'attribute-overrides'})) {
            $fieldBuilder = new Builder\FieldMetadataBuilder($metadataBuildingContext);

            $fieldBuilder
                ->withComponentMetadata($metadata);

            foreach ($xmlRoot->{'attribute-overrides'}->{'attribute-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];
                $property  = $metadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($metadata->getClassName(), $fieldName);
                }

                foreach ($overrideElement->field as $fieldElement) {
                    $versionAnnotation = isset($fieldElement['version']) && $this->evaluateBoolean($fieldElement['version'])
                        ? new Annotation\Version()
                        : null;

                    $fieldBuilder
                        ->withFieldName($fieldName)
                        ->withColumnAnnotation($this->convertFieldElementToColumnAnnotation($fieldElement))
                        ->withIdAnnotation(null)
                        ->withVersionAnnotation($versionAnnotation);

                    $fieldMetadata = $fieldBuilder->build();
                    $columnName    = $fieldMetadata->getColumnName();

                    // Prevent column duplication
                    if ($metadata->checkPropertyDuplication($columnName)) {
                        throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                    }

                    $metadata->fieldNames[$fieldMetadata->getColumnName()] = $fieldName;

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

                $override = clone $property;

                // Check for join-columns
                if (isset($overrideElement->{'join-columns'})) {
                    $joinColumnBuilder = new Builder\JoinColumnMetadataBuilder($metadataBuildingContext);

                    $joinColumnBuilder
                        ->withComponentMetadata($metadata)
                        ->withFieldName($override->getName());

                    $joinColumns = [];

                    foreach ($overrideElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                        $joinColumnBuilder->withJoinColumnAnnotation(
                            $this->convertJoinColumnElementToJoinColumnAnnotation($joinColumnElement)
                        );

                        $joinColumnMetadata = $joinColumnBuilder->build();
                        $columnName         = $joinColumnMetadata->getColumnName();

                        // @todo guilhermeblanco Open an issue to discuss making this scenario impossible.
                        //if ($metadata->checkPropertyDuplication($columnName)) {
                        //    throw Mapping\MappingException::duplicateColumnName($metadata->getClassName(), $columnName);
                        //}

                        if ($override->isOwningSide()) {
                            $metadata->fieldNames[$columnName] = $fieldName;
                        }

                        $joinColumns[] = $joinColumnMetadata;
                    }

                    $override->setJoinColumns($joinColumns);
                }

                // Check for join-table
                if ($overrideElement->{'join-table'}) {
                    $joinTableElement    = $overrideElement->{'join-table'};
                    $joinTableAnnotation = $this->convertJoinTableElementToJoinTableAnnotation($joinTableElement);
                    $joinTableBuilder    = new Builder\JoinTableMetadataBuilder($metadataBuildingContext);

                    $joinTableBuilder
                        ->withComponentMetadata($metadata)
                        ->withFieldName($property->getName())
                        ->withTargetEntity($property->getTargetEntity())
                        ->withJoinTableAnnotation($joinTableAnnotation);

                    $override->setJoinTable($joinTableBuilder->build());
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

                $metadata->addLifecycleCallback($eventName, $methodName);
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

                foreach ($listenerElement as $callbackElement) {
                    $eventName  = (string) $callbackElement['type'];
                    $methodName = (string) $callbackElement['method'];

                    $metadata->addEntityListener($eventName, $listenerClassName, $methodName);
                }
            }
        }

        return $metadata;
    }

    /**
     * Parses (nested) index elements.
     *
     * @param SimpleXMLElement $indexes The XML element.
     *
     * @return Annotation\Index[] The indexes array.
     */
    private function parseIndexes(SimpleXMLElement $indexes) : array
    {
        $array = [];

        /** @var SimpleXMLElement $index */
        foreach ($indexes as $index) {
            $indexAnnotation = new Annotation\Index();

            $indexAnnotation->columns = explode(',', (string) $index['columns']);
            $indexAnnotation->options = isset($index->options) ? $this->parseOptions($index->options->children()) : [];
            $indexAnnotation->flags   = isset($index['flags']) ? explode(',', (string) $index['flags']) : [];

            if (isset($index['name'])) {
                $indexAnnotation->name = (string) $index['name'];
            }

            if (isset($index['unique'])) {
                $indexAnnotation->unique = $this->evaluateBoolean($index['unique']);
            }

            $array[] = $indexAnnotation;
        }

        return $array;
    }

    /**
     * Parses (nested) unique constraint elements.
     *
     * @param SimpleXMLElement $uniqueConstraints The XML element.
     *
     * @return Annotation\UniqueConstraint[] The unique constraints array.
     */
    private function parseUniqueConstraints(SimpleXMLElement $uniqueConstraints) : array
    {
        $array = [];

        /** @var SimpleXMLElement $uniqueConstraint */
        foreach ($uniqueConstraints as $uniqueConstraint) {
            $uniqueConstraintAnnotation = new Annotation\UniqueConstraint();

            $uniqueConstraintAnnotation->columns = explode(',', (string) $uniqueConstraint['columns']);
            $uniqueConstraintAnnotation->options = isset($uniqueConstraint->options) ? $this->parseOptions($uniqueConstraint->options->children()) : [];
            $uniqueConstraintAnnotation->flags   = isset($uniqueConstraint['flags']) ? explode(',', (string) $uniqueConstraint['flags']) : [];

            if (isset($uniqueConstraint['name'])) {
                $uniqueConstraintAnnotation->name = (string) $uniqueConstraint['name'];
            }

            $array[] = $uniqueConstraintAnnotation;
        }

        return $array;
    }

    /**
     * Parses (nested) option elements.
     *
     * @param SimpleXMLElement $options The XML element.
     *
     * @return mixed[] The options array.
     */
    private function parseOptions(SimpleXMLElement $options) : array
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
                $nameAttribute = (string) $attributes->name;

                $array[$nameAttribute] = in_array($nameAttribute, ['unsigned', 'fixed'], true)
                    ? $this->evaluateBoolean($value)
                    : $value;
            } else {
                $array[] = $value;
            }
        }

        return $array;
    }

    private function convertOneToOneElementToOneToOneAnnotation(
        SimpleXMLElement $oneToOneElement
    ) : Annotation\OneToOne {
        $oneToOneAnnotation = new Annotation\OneToOne();

        $oneToOneAnnotation->targetEntity = (string) $oneToOneElement['target-entity'];

        if (isset($oneToOneElement['mapped-by'])) {
            $oneToOneAnnotation->mappedBy = (string) $oneToOneElement['mapped-by'];
        }

        if (isset($oneToOneElement['inversed-by'])) {
            $oneToOneAnnotation->inversedBy = (string) $oneToOneElement['inversed-by'];
        }

        if (isset($oneToOneElement['orphan-removal'])) {
            $oneToOneAnnotation->orphanRemoval = $this->evaluateBoolean($oneToOneElement['orphan-removal']);
        }

        if (isset($oneToOneElement['fetch'])) {
            $oneToOneAnnotation->fetch = (string) $oneToOneElement['fetch'];
        }

        if (isset($oneToOneElement->cascade)) {
            $oneToOneAnnotation->cascade = $this->getCascadeMappings($oneToOneElement->cascade);
        }

        return $oneToOneAnnotation;
    }

    private function convertManyToOneElementToManyToOneAnnotation(
        SimpleXMLElement $manyToOneElement
    ) : Annotation\ManyToOne {
        $manyToOneAnnotation = new Annotation\ManyToOne();

        $manyToOneAnnotation->targetEntity = (string) $manyToOneElement['target-entity'];

        if (isset($manyToOneElement['inversed-by'])) {
            $manyToOneAnnotation->inversedBy = (string) $manyToOneElement['inversed-by'];
        }

        if (isset($manyToOneElement['fetch'])) {
            $manyToOneAnnotation->fetch = (string) $manyToOneElement['fetch'];
        }

        if (isset($manyToOneElement->cascade)) {
            $manyToOneAnnotation->cascade = $this->getCascadeMappings($manyToOneElement->cascade);
        }

        return $manyToOneAnnotation;
    }

    private function convertOneToManyElementToOneToManyAnnotation(
        SimpleXMLElement $oneToManyElement
    ) : Annotation\OneToMany {
        $oneToManyAnnotation = new Annotation\OneToMany();

        $oneToManyAnnotation->targetEntity = (string) $oneToManyElement['target-entity'];

        if (isset($oneToManyElement['mapped-by'])) {
            $oneToManyAnnotation->mappedBy = (string) $oneToManyElement['mapped-by'];
        }

        if (isset($oneToManyElement['fetch'])) {
            $oneToManyAnnotation->fetch = (string) $oneToManyElement['fetch'];
        }

        if (isset($oneToManyElement->cascade)) {
            $oneToManyAnnotation->cascade = $this->getCascadeMappings($oneToManyElement->cascade);
        }

        if (isset($oneToManyElement['orphan-removal'])) {
            $oneToManyAnnotation->orphanRemoval = $this->evaluateBoolean($oneToManyElement['orphan-removal']);
        }

        if (isset($oneToManyElement['index-by'])) {
            $oneToManyAnnotation->indexBy = (string) $oneToManyElement['index-by'];
        } elseif (isset($oneToManyElement->{'index-by'})) {
            throw new InvalidArgumentException('<index-by /> is not a valid tag');
        }

        return $oneToManyAnnotation;
    }

    private function convertManyToManyElementToManyToManyAnnotation(
        SimpleXMLElement $manyToManyElement
    ) : Annotation\ManyToMany {
        $manyToManyAnnotation = new Annotation\ManyToMany();

        $manyToManyAnnotation->targetEntity = (string) $manyToManyElement['target-entity'];

        if (isset($manyToManyElement['mapped-by'])) {
            $manyToManyAnnotation->mappedBy = (string) $manyToManyElement['mapped-by'];
        }

        if (isset($manyToManyElement['inversed-by'])) {
            $manyToManyAnnotation->inversedBy = (string) $manyToManyElement['inversed-by'];
        }

        if (isset($manyToManyElement['fetch'])) {
            $manyToManyAnnotation->fetch = (string) $manyToManyElement['fetch'];
        }

        if (isset($manyToManyElement->cascade)) {
            $manyToManyAnnotation->cascade = $this->getCascadeMappings($manyToManyElement->cascade);
        }

        if (isset($manyToManyElement['orphan-removal'])) {
            $manyToManyAnnotation->orphanRemoval = $this->evaluateBoolean($manyToManyElement['orphan-removal']);
        }

        if (isset($manyToManyElement['index-by'])) {
            $manyToManyAnnotation->indexBy = (string) $manyToManyElement['index-by'];
        } elseif (isset($manyToManyElement->{'index-by'})) {
            throw new InvalidArgumentException('<index-by /> is not a valid tag');
        }

        return $manyToManyAnnotation;
    }

    private function convertFieldElementToColumnAnnotation(
        SimpleXMLElement $fieldElement
    ) : Annotation\Column {
        $columnAnnotation = new Annotation\Column();

        $columnAnnotation->type = isset($fieldElement['type']) ? (string) $fieldElement['type'] : 'string';

        if (isset($fieldElement['column'])) {
            $columnAnnotation->name = (string) $fieldElement['column'];
        }

        if (isset($fieldElement['length'])) {
            $columnAnnotation->length = (int) $fieldElement['length'];
        }

        if (isset($fieldElement['precision'])) {
            $columnAnnotation->precision = (int) $fieldElement['precision'];
        }

        if (isset($fieldElement['scale'])) {
            $columnAnnotation->scale = (int) $fieldElement['scale'];
        }

        if (isset($fieldElement['unique'])) {
            $columnAnnotation->unique = $this->evaluateBoolean($fieldElement['unique']);
        }

        if (isset($fieldElement['nullable'])) {
            $columnAnnotation->nullable = $this->evaluateBoolean($fieldElement['nullable']);
        }

        if (isset($fieldElement['column-definition'])) {
            $columnAnnotation->columnDefinition = (string) $fieldElement['column-definition'];
        }

        if (isset($fieldElement->options)) {
            $columnAnnotation->options = $this->parseOptions($fieldElement->options->children());
        }

        return $columnAnnotation;
    }

    private function convertGeneratorElementToGeneratedValueAnnotation(
        SimpleXMLElement $generatorElement
    ) : Annotation\GeneratedValue {
        $generatedValueAnnotation = new Annotation\GeneratedValue();

        $generatedValueAnnotation->strategy = (string) ($generatorElement['strategy'] ?? 'AUTO');

        return $generatedValueAnnotation;
    }

    private function convertSequenceGeneratorElementToSequenceGeneratorAnnotation(
        SimpleXMLElement $sequenceGeneratorElement
    ) : Annotation\SequenceGenerator {
        $sequenceGeneratorAnnotation = new Annotation\SequenceGenerator();

        $sequenceGeneratorAnnotation->sequenceName   = (string) ($sequenceGeneratorElement['sequence-name'] ?? null);
        $sequenceGeneratorAnnotation->allocationSize = (int) ($sequenceGeneratorElement['allocation-size'] ?? 1);

        return $sequenceGeneratorAnnotation;
    }

    private function convertCustomIdGeneratorElementToCustomIdGeneratorAnnotation(
        SimpleXMLElement $customIdGeneratorElement
    ) : Annotation\CustomIdGenerator {
        $customIdGeneratorAnnotation = new Annotation\CustomIdGenerator();

        $customIdGeneratorAnnotation->class     = (string) $customIdGeneratorElement['class'];
        $customIdGeneratorAnnotation->arguments = [];

        return $customIdGeneratorAnnotation;
    }

    /**
     * Constructs a JoinTable annotation based on the information
     * found in the given SimpleXMLElement.
     *
     * @param SimpleXMLElement $joinTableElement The XML element.
     */
    private function convertJoinTableElementToJoinTableAnnotation(
        SimpleXMLElement $joinTableElement
    ) : Annotation\JoinTable {
        $joinTableAnnotation = new Annotation\JoinTable();

        if (isset($joinTableElement['name'])) {
            $joinTableAnnotation->name = (string) $joinTableElement['name'];
        }

        if (isset($joinTableElement['schema'])) {
            $joinTableAnnotation->schema = (string) $joinTableElement['schema'];
        }

        if (isset($joinTableElement->{'join-columns'})) {
            $joinColumns = [];

            foreach ($joinTableElement->{'join-columns'}->{'join-column'} as $joinColumnElement) {
                $joinColumns[] = $this->convertJoinColumnElementToJoinColumnAnnotation($joinColumnElement);
            }

            $joinTableAnnotation->joinColumns = $joinColumns;
        }

        if (isset($joinTableElement->{'inverse-join-columns'})) {
            $joinColumns = [];

            foreach ($joinTableElement->{'inverse-join-columns'}->{'join-column'} as $joinColumnElement) {
                $joinColumns[] = $this->convertJoinColumnElementToJoinColumnAnnotation($joinColumnElement);
            }

            $joinTableAnnotation->inverseJoinColumns = $joinColumns;
        }

        return $joinTableAnnotation;
    }

    private function convertJoinColumnsElementToJoinColumnsAnnotation(
        SimpleXMLElement $joinColumnsElement
    ) : Annotation\JoinColumns {
        $joinColumnsAnnotation = new Annotation\JoinColumns();
        $joinColumns           = [];

        foreach ($joinColumnsElement->{'join-column'} as $joinColumnElement) {
            $joinColumns[] = $this->convertJoinColumnElementToJoinColumnAnnotation($joinColumnElement);
        }

        $joinColumnsAnnotation->value = $joinColumns;

        return $joinColumnsAnnotation;
    }

    /**
     * Constructs a JoinColumn annotation based on the information
     * found in the given SimpleXMLElement.
     *
     * @param SimpleXMLElement $joinColumnElement The XML element.
     */
    private function convertJoinColumnElementToJoinColumnAnnotation(
        SimpleXMLElement $joinColumnElement
    ) : Annotation\JoinColumn {
        $joinColumnAnnotation = new Annotation\JoinColumn();

        $joinColumnAnnotation->name                 = (string) $joinColumnElement['name'];
        $joinColumnAnnotation->referencedColumnName = (string) $joinColumnElement['referenced-column-name'];

        if (isset($joinColumnElement['column-definition'])) {
            $joinColumnAnnotation->columnDefinition = (string) $joinColumnElement['column-definition'];
        }

        if (isset($joinColumnElement['field-name'])) {
            $joinColumnAnnotation->fieldName = (string) $joinColumnElement['field-name'];
        }

        if (isset($joinColumnElement['nullable'])) {
            $joinColumnAnnotation->nullable = $this->evaluateBoolean($joinColumnElement['nullable']);
        }

        if (isset($joinColumnElement['unique'])) {
            $joinColumnAnnotation->unique = $this->evaluateBoolean($joinColumnElement['unique']);
        }

        if (isset($joinColumnElement['on-delete'])) {
            $joinColumnAnnotation->onDelete = strtoupper((string) $joinColumnElement['on-delete']);
        }

        return $joinColumnAnnotation;
    }

    /**
     * Parse the given Cache as CacheMetadata
     */
    private function convertCacheElementToCacheAnnotation(SimpleXMLElement $cacheMapping) : Annotation\Cache
    {
        $cacheAnnotation = new Annotation\Cache();

        if (isset($cacheMapping['region'])) {
            $cacheAnnotation->region = (string) $cacheMapping['region'];
        }

        if (isset($cacheMapping['usage'])) {
            $cacheAnnotation->usage = strtoupper((string) $cacheMapping['usage']);
        }

        return $cacheAnnotation;
    }

    private function convertDiscrimininatorColumnElementToDiscriminatorColumnAnnotation(
        SimpleXMLElement $discriminatorColumnElement
    ) : Annotation\DiscriminatorColumn {
        $discriminatorColumnAnnotation = new Annotation\DiscriminatorColumn();

        $discriminatorColumnAnnotation->type = (string) ($discriminatorColumnElement['type'] ?? 'string');
        $discriminatorColumnAnnotation->name = (string) $discriminatorColumnElement['name'];

        if (isset($discriminatorColumnElement['column-definition'])) {
            $discriminatorColumnAnnotation->columnDefinition = (string) $discriminatorColumnElement['column-definition'];
        }

        if (isset($discriminatorColumnElement['length'])) {
            $discriminatorColumnAnnotation->length = (int) $discriminatorColumnElement['length'];
        }

        return $discriminatorColumnAnnotation;
    }

    private function convertOrderByElementToOrderByAnnotation(
        SimpleXMLElement $orderByElement
    ) : Annotation\OrderBy {
        $orderByAnnotation = new Annotation\OrderBy();
        $orderBy           = [];

        foreach ($orderByElement->{'order-by-field'} as $orderByField) {
            $orderBy[(string) $orderByField['name']] = isset($orderByField['direction'])
                ? (string) $orderByField['direction']
                : Criteria::ASC;
        }

        $orderByAnnotation->value = $orderBy;

        return $orderByAnnotation;
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param SimpleXMLElement $cascadeElement The cascade element.
     *
     * @return string[] The list of cascade options.
     */
    private function getCascadeMappings(SimpleXMLElement $cascadeElement) : array
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
