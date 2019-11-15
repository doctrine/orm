<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\Common\Collections\Criteria;
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

    public function loadMetadataForClass(
        string $className,
        ?Mapping\ComponentMetadata $parent,
        Mapping\ClassMetadataBuildingContext $metadataBuildingContext
    ) : Mapping\ComponentMetadata {
        /** @var SimpleXMLElement $xmlRoot */
        $xmlRoot       = $this->getElement($className);
        $classBuilder  = new Builder\ClassMetadataBuilder($metadataBuildingContext);
        $classMetadata = $classBuilder
            ->withClassName($className)
            ->withParentMetadata($parent)
            ->withEntityAnnotation(
                $xmlRoot->getName() === 'entity'
                    ? $this->convertEntityElementToEntityAnnotation($xmlRoot)
                    : null
            )
            ->withMappedSuperclassAnnotation(
                $xmlRoot->getName() === 'mapped-superclass'
                    ? $this->convertMappedSuperclassElementToMappedSuperclassAnnotation($xmlRoot)
                    : null
            )
            ->withEmbeddableAnnotation(
                $xmlRoot->getName() === 'embeddable'
                    ? null
                    : null
            )
            ->withTableAnnotation(
                // @todo guilhermeblanco Is this the proper check to build Table annotation?
                $xmlRoot->getName() === 'entity'
                    ? $this->convertTableElementToTableAnnotation($xmlRoot)
                    : null
            )
            ->withInheritanceTypeAnnotation(
                isset($xmlRoot['inheritance-type'])
                    ? $this->convertInheritanceTypeElementToInheritanceTypeAnnotation($xmlRoot)
                    : null
            )
            ->withDiscriminatorColumnAnnotation(
                isset($xmlRoot->{'discriminator-column'})
                    ? $this->convertDiscrimininatorColumnElementToDiscriminatorColumnAnnotation($xmlRoot->{'discriminator-column'})
                    : null
            )
            ->withDiscriminatorMapAnnotation(
                isset($xmlRoot->{'discriminator-map'})
                    ? $this->convertDiscriminatorMapElementToDiscriminatorMapAnnotation($xmlRoot->{'discriminator-map'})
                    : null
            )
            ->withChangeTrackingPolicyAnnotation(
                isset($xmlRoot['change-tracking-policy'])
                    ? $this->convertChangeTrackingPolicyElementToChangeTrackingPolicyAnnotation($xmlRoot)
                    : null
            )
            ->withCacheAnnotation(
                isset($xmlRoot->cache)
                    ? $this->convertCacheElementToCacheAnnotation($xmlRoot->cache)
                    : null
            )
            ->build();

        $propertyBuilder = new Builder\PropertyMetadataBuilder($metadataBuildingContext);

        $propertyBuilder->withComponentMetadata($classMetadata);

        // Evaluate <field ...> mappings
        if (isset($xmlRoot->field)) {
            foreach ($xmlRoot->field as $fieldElement) {
                $propertyBuilder
                    ->withFieldName((string) $fieldElement['name'])
                    ->withColumnAnnotation($this->convertFieldElementToColumnAnnotation($fieldElement))
                    ->withIdAnnotation(null)
                    ->withVersionAnnotation(
                        isset($fieldElement['version']) && $this->evaluateBoolean($fieldElement['version'])
                            ? new Annotation\Version()
                            : null
                    );

                $classMetadata->addProperty($propertyBuilder->build());
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

                $classMetadata->mapEmbedded($mapping);
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

            $propertyBuilder
                ->withFieldName($fieldName)
                ->withColumnAnnotation($this->convertFieldElementToColumnAnnotation($idElement))
                ->withIdAnnotation(new Annotation\Id())
                ->withVersionAnnotation(
                    isset($idElement['version']) && $this->evaluateBoolean($idElement['version'])
                        ? new Annotation\Version()
                        : null
                )
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
                );

            $classMetadata->addProperty($propertyBuilder->build());
        }

        // Evaluate <one-to-one ...> mappings
        if (isset($xmlRoot->{'one-to-one'})) {
            foreach ($xmlRoot->{'one-to-one'} as $oneToOneElement) {
                $fieldName = (string) $oneToOneElement['field'];

                $propertyBuilder
                    ->withFieldName($fieldName)
                    ->withOneToOneAnnotation($this->convertOneToOneElementToOneToOneAnnotation($oneToOneElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withVersionAnnotation(null)
                    ->withCacheAnnotation(
                        isset($oneToOneElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($oneToOneElement->cache)
                            : null
                    )
                    ->withJoinColumnAnnotation(
                        isset($oneToOneElement->{'join-column'})
                            ? $this->convertJoinColumnElementToJoinColumnAnnotation($oneToOneElement->{'join-column'})
                            : null
                    )
                    ->withJoinColumnsAnnotation(
                        isset($oneToOneElement->{'join-columns'})
                            ? $this->convertJoinColumnsElementToJoinColumnsAnnotation($oneToOneElement->{'join-columns'})
                            : null
                    );

                $classMetadata->addProperty($propertyBuilder->build());
            }
        }

        // Evaluate <many-to-one ...> mappings
        if (isset($xmlRoot->{'many-to-one'})) {
            foreach ($xmlRoot->{'many-to-one'} as $manyToOneElement) {
                $fieldName = (string) $manyToOneElement['field'];

                $propertyBuilder
                    ->withFieldName($fieldName)
                    ->withManyToOneAnnotation($this->convertManyToOneElementToManyToOneAnnotation($manyToOneElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withVersionAnnotation(null)
                    ->withCacheAnnotation(
                        isset($manyToOneElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($manyToOneElement->cache)
                            : null
                    )
                    ->withJoinColumnAnnotation(
                        isset($manyToOneElement->{'join-column'})
                            ? $this->convertJoinColumnElementToJoinColumnAnnotation($manyToOneElement->{'join-column'})
                            : null
                    )
                    ->withJoinColumnsAnnotation(
                        isset($manyToOneElement->{'join-columns'})
                            ? $this->convertJoinColumnsElementToJoinColumnsAnnotation($manyToOneElement->{'join-columns'})
                            : null
                    );

                $classMetadata->addProperty($propertyBuilder->build());
            }
        }

        // Evaluate <one-to-many ...> mappings
        if (isset($xmlRoot->{'one-to-many'})) {
            foreach ($xmlRoot->{'one-to-many'} as $oneToManyElement) {
                $fieldName = (string) $oneToManyElement['field'];

                $propertyBuilder
                    ->withFieldName($fieldName)
                    ->withOneToManyAnnotation($this->convertOneToManyElementToOneToManyAnnotation($oneToManyElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withVersionAnnotation(null)
                    ->withCacheAnnotation(
                        isset($oneToManyElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($oneToManyElement->cache)
                            : null
                    )
                    ->withOrderByAnnotation(
                        isset($oneToManyElement->{'order-by'})
                            ? $this->convertOrderByElementToOrderByAnnotation($oneToManyElement->{'order-by'})
                            : null
                    );

                $classMetadata->addProperty($propertyBuilder->build());
            }
        }

        // Evaluate <many-to-many ...> mappings
        if (isset($xmlRoot->{'many-to-many'})) {
            foreach ($xmlRoot->{'many-to-many'} as $manyToManyElement) {
                $fieldName = (string) $manyToManyElement['field'];

                $propertyBuilder
                    ->withFieldName($fieldName)
                    ->withManyToManyAnnotation($this->convertManyToManyElementToManyToManyAnnotation($manyToManyElement))
                    ->withIdAnnotation(isset($associationIds[$fieldName]) ? new Annotation\Id() : null)
                    ->withVersionAnnotation(null)
                    ->withCacheAnnotation(
                        isset($manyToManyElement->cache)
                            ? $this->convertCacheElementToCacheAnnotation($manyToManyElement->cache)
                            : null
                    )
                    ->withJoinTableAnnotation(
                        isset($manyToManyElement->{'join-table'})
                            ? $this->convertJoinTableElementToJoinTableAnnotation($manyToManyElement->{'join-table'})
                            : null
                    )
                    ->withOrderByAnnotation(
                        isset($manyToManyElement->{'order-by'})
                            ? $this->convertOrderByElementToOrderByAnnotation($manyToManyElement->{'order-by'})
                            : null
                    );

                $classMetadata->addProperty($propertyBuilder->build());
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'attribute-overrides'})) {
            $fieldBuilder = new Builder\FieldMetadataBuilder($metadataBuildingContext);

            $fieldBuilder
                ->withComponentMetadata($classMetadata);

            foreach ($xmlRoot->{'attribute-overrides'}->{'attribute-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];
                $property  = $classMetadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($classMetadata->getClassName(), $fieldName);
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
                    if ($classMetadata->checkPropertyDuplication($columnName)) {
                        throw Mapping\MappingException::duplicateColumnName($classMetadata->getClassName(), $columnName);
                    }

                    $classMetadata->setPropertyOverride($fieldMetadata);
                }
            }
        }

        // Evaluate association-overrides
        if (isset($xmlRoot->{'association-overrides'})) {
            foreach ($xmlRoot->{'association-overrides'}->{'association-override'} as $overrideElement) {
                $fieldName = (string) $overrideElement['name'];
                $property  = $classMetadata->getProperty($fieldName);

                if (! $property) {
                    throw Mapping\MappingException::invalidOverrideFieldName($classMetadata->getClassName(), $fieldName);
                }

                $override = clone $property;

                // Check for join-columns
                if (isset($overrideElement->{'join-columns'})) {
                    $joinColumnBuilder = new Builder\JoinColumnMetadataBuilder($metadataBuildingContext);

                    $joinColumnBuilder
                        ->withComponentMetadata($classMetadata)
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
                        ->withComponentMetadata($classMetadata)
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
                        \constant('Doctrine\ORM\Mapping\FetchMode::' . (string) $overrideElement['fetch'])
                    );
                }

                $classMetadata->setPropertyOverride($override);
            }
        }

        // Evaluate <lifecycle-callbacks...>
        if (isset($xmlRoot->{'lifecycle-callbacks'})) {
            foreach ($xmlRoot->{'lifecycle-callbacks'}->{'lifecycle-callback'} as $lifecycleCallback) {
                $eventName  = \constant(Events::class . '::' . (string) $lifecycleCallback['type']);
                $methodName = (string) $lifecycleCallback['method'];

                $classMetadata->addLifecycleCallback($eventName, $methodName);
            }
        }

        // Evaluate entity listener
        if (isset($xmlRoot->{'entity-listeners'})) {
            foreach ($xmlRoot->{'entity-listeners'}->{'entity-listener'} as $listenerElement) {
                $listenerClassName = (string) $listenerElement['class'];

                if (! \class_exists($listenerClassName)) {
                    throw Mapping\MappingException::entityListenerClassNotFound(
                        $listenerClassName,
                        $classMetadata->getClassName()
                    );
                }

                foreach ($listenerElement as $callbackElement) {
                    $eventName  = (string) $callbackElement['type'];
                    $methodName = (string) $callbackElement['method'];

                    $classMetadata->addEntityListener($eventName, $listenerClassName, $methodName);
                }
            }
        }

        return $classMetadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = [];
        // Note: we do not use `simplexml_load_file()` because of https://bugs.php.net/bug.php?id=62577
        $xmlElement = \simplexml_load_string(\file_get_contents($file));

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

    private function convertEntityElementToEntityAnnotation(
        SimpleXMLElement $entityElement
    ) : Annotation\Entity {
        $entityAnnotation = new Annotation\Entity();

        if (isset($entityElement['repository-class'])) {
            $entityAnnotation->repositoryClass = (string) $entityElement['repository-class'];
        }

        if (isset($entityElement['read-only'])) {
            $entityAnnotation->readOnly = $this->evaluateBoolean($entityElement['read-only']);
        }

        return $entityAnnotation;
    }

    private function convertMappedSuperclassElementToMappedSuperclassAnnotation(
        SimpleXMLElement $mappedSuperclassElement
    ) : Annotation\MappedSuperclass {
        $mappedSuperclassAnnotation = new Annotation\MappedSuperclass();

        if (isset($mappedSuperclassElement['repository-class'])) {
            $mappedSuperclassAnnotation->repositoryClass = (string) $mappedSuperclassElement['repository-class'];
        }

        return $mappedSuperclassAnnotation;
    }

    private function convertTableElementToTableAnnotation(
        SimpleXMLElement $tableElement
    ) : Annotation\Table {
        $tableAnnotation = new Annotation\Table();

        // Evaluate <entity...> attributes
        if (isset($tableElement['table'])) {
            $tableAnnotation->name = (string) $tableElement['table'];
        }

        if (isset($tableElement['schema'])) {
            $tableAnnotation->schema = (string) $tableElement['schema'];
        }

        // Evaluate <indexes...>
        if (isset($tableElement->indexes)) {
            $indexes = [];

            /** @var SimpleXMLElement $indexElement */
            foreach ($tableElement->indexes->children() as $indexElement) {
                $indexes[] = $this->convertIndexElementToIndexAnnotation($indexElement);
            }

            $tableAnnotation->indexes = $indexes;
        }

        // Evaluate <unique-constraints..>
        if (isset($tableElement->{'unique-constraints'})) {
            $uniqueConstraints = [];

            foreach ($tableElement->{'unique-constraints'}->children() as $uniqueConstraintElement) {
                $uniqueConstraints[] = $this->convertUniqueConstraintElementToUniqueConstraintAnnotation($uniqueConstraintElement);
            }

            $tableAnnotation->uniqueConstraints = $uniqueConstraints;
        }

        if (isset($tableElement->options)) {
            $tableAnnotation->options = $this->parseOptions($tableElement->options->children());
        }

        return $tableAnnotation;
    }

    private function convertIndexElementToIndexAnnotation(
        SimpleXMLElement $indexElement
    ) : Annotation\Index {
        $indexAnnotation = new Annotation\Index();

        $indexAnnotation->columns = \explode(',', (string) $indexElement['columns']);
        $indexAnnotation->options = isset($indexElement->options) ? $this->parseOptions($indexElement->options->children()) : [];
        $indexAnnotation->flags   = isset($indexElement['flags']) ? \explode(',', (string) $indexElement['flags']) : [];

        if (isset($indexElement['name'])) {
            $indexAnnotation->name = (string) $indexElement['name'];
        }

        if (isset($indexElement['unique'])) {
            $indexAnnotation->unique = $this->evaluateBoolean($indexElement['unique']);
        }

        return $indexAnnotation;
    }

    private function convertUniqueConstraintElementToUniqueConstraintAnnotation(
        SimpleXMLElement $uniqueConstraintElement
    ) : Annotation\UniqueConstraint {
        $uniqueConstraintAnnotation = new Annotation\UniqueConstraint();

        $uniqueConstraintAnnotation->columns = \explode(',', (string) $uniqueConstraintElement['columns']);
        $uniqueConstraintAnnotation->options = isset($uniqueConstraintElement->options) ? $this->parseOptions($uniqueConstraintElement->options->children()) : [];
        $uniqueConstraintAnnotation->flags   = isset($uniqueConstraintElement['flags']) ? \explode(',', (string) $uniqueConstraintElement['flags']) : [];

        if (isset($uniqueConstraintElement['name'])) {
            $uniqueConstraintAnnotation->name = (string) $uniqueConstraintElement['name'];
        }

        return $uniqueConstraintAnnotation;
    }

    private function convertInheritanceTypeElementToInheritanceTypeAnnotation(
        SimpleXMLElement $inheritanceTypeElement
    ) : Annotation\InheritanceType {
        $inheritanceTypeAnnotation = new Annotation\InheritanceType();

        $inheritanceTypeAnnotation->value = \strtoupper((string) $inheritanceTypeElement['inheritance-type']);

        return $inheritanceTypeAnnotation;
    }

    private function convertChangeTrackingPolicyElementToChangeTrackingPolicyAnnotation(
        SimpleXMLElement $changeTrackingPolicyElement
    ) : Annotation\ChangeTrackingPolicy {
        $changeTrackingPolicyAnnotation = new Annotation\ChangeTrackingPolicy();

        $changeTrackingPolicyAnnotation->value = \strtoupper((string) $changeTrackingPolicyElement['change-tracking-policy']);

        return $changeTrackingPolicyAnnotation;
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

    private function convertDiscriminatorMapElementToDiscriminatorMapAnnotation(
        SimpleXMLElement $discriminatorMapElement
    ) : Annotation\DiscriminatorMap {
        $discriminatorMapAnnotation = new Annotation\DiscriminatorMap();
        $discriminatorMap           = [];

        foreach ($discriminatorMapElement->{'discriminator-mapping'} as $discriminatorMapElement) {
            $discriminatorMap[(string) $discriminatorMapElement['value']] = (string) $discriminatorMapElement['class'];
        }

        $discriminatorMapAnnotation->value = $discriminatorMap;

        return $discriminatorMapAnnotation;
    }

    private function convertCacheElementToCacheAnnotation(
        SimpleXMLElement $cacheElement
    ) : Annotation\Cache {
        $cacheAnnotation = new Annotation\Cache();

        if (isset($cacheElement['region'])) {
            $cacheAnnotation->region = (string) $cacheElement['region'];
        }

        if (isset($cacheElement['usage'])) {
            $cacheAnnotation->usage = \strtoupper((string) $cacheElement['usage']);
        }

        return $cacheAnnotation;
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
            $joinColumnAnnotation->onDelete = \strtoupper((string) $joinColumnElement['on-delete']);
        }

        return $joinColumnAnnotation;
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
            $cascades[] = \str_replace('cascade-', '', $action->getName());
        }

        return $cascades;
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    private function evaluateBoolean($element)
    {
        $flag = (string) $element;

        return $flag === 'true' || $flag === '1';
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

                $array[$nameAttribute] = \in_array($nameAttribute, ['unsigned', 'fixed'], true)
                    ? $this->evaluateBoolean($value)
                    : $value;
            } else {
                $array[] = $value;
            }
        }

        return $array;
    }
}
