<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Exception;
use RuntimeException;
use ValueError;

use function array_keys;
use function array_search;
use function count;
use function in_array;
use function key;
use function reset;
use function sprintf;

class SimpleObjectHydrator extends AbstractHydrator
{
    use SQLResultCasing;

    /** @var ClassMetadata */
    private $class;

    /**
     * {@inheritdoc}
     */
    protected function prepare()
    {
        if (count($this->resultSetMapping()->aliasMap) !== 1) {
            throw new RuntimeException('Cannot use SimpleObjectHydrator with a ResultSetMapping that contains more than one object result.');
        }

        if ($this->resultSetMapping()->scalarMappings) {
            throw new RuntimeException('Cannot use SimpleObjectHydrator with a ResultSetMapping that contains scalar mappings.');
        }

        $this->class = $this->getClassMetadata(reset($this->resultSetMapping()->aliasMap));
    }

    /**
     * {@inheritdoc}
     */
    protected function cleanup()
    {
        parent::cleanup();

        $this->_uow->triggerEagerLoads();
        $this->_uow->hydrationComplete();
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];

        while ($row = $this->statement()->fetchAssociative()) {
            $this->hydrateRowData($row, $result);
        }

        $this->_em->getUnitOfWork()->triggerEagerLoads();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $row, array &$result)
    {
        $entityName       = $this->class->name;
        $data             = [];
        $discrColumnValue = null;

        // We need to find the correct entity class name if we have inheritance in resultset
        if ($this->class->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $discrColumn     = $this->class->getDiscriminatorColumn();
            $discrColumnName = $this->getSQLResultCasing($this->_platform, $discrColumn['name']);

            // Find mapped discriminator column from the result set.
            $metaMappingDiscrColumnName = array_search($discrColumnName, $this->resultSetMapping()->metaMappings, true);
            if ($metaMappingDiscrColumnName) {
                $discrColumnName = $metaMappingDiscrColumnName;
            }

            if (! isset($row[$discrColumnName])) {
                throw HydrationException::missingDiscriminatorColumn(
                    $entityName,
                    $discrColumnName,
                    key($this->resultSetMapping()->aliasMap)
                );
            }

            if ($row[$discrColumnName] === '') {
                throw HydrationException::emptyDiscriminatorValue(key(
                    $this->resultSetMapping()->aliasMap
                ));
            }

            $discrMap = $this->class->discriminatorMap;

            if (! isset($discrMap[$row[$discrColumnName]])) {
                throw HydrationException::invalidDiscriminatorValue($row[$discrColumnName], array_keys($discrMap));
            }

            $entityName       = $discrMap[$row[$discrColumnName]];
            $discrColumnValue = $row[$discrColumnName];

            unset($row[$discrColumnName]);
        }

        foreach ($row as $column => $value) {
            // An ObjectHydrator should be used instead of SimpleObjectHydrator
            if (isset($this->resultSetMapping()->relationMap[$column])) {
                throw new Exception(sprintf('Unable to retrieve association information for column "%s"', $column));
            }

            $cacheKeyInfo = $this->hydrateColumnInfo($column);

            if (! $cacheKeyInfo) {
                continue;
            }

            // If we have inheritance in resultset, make sure the field belongs to the correct class
            if (isset($cacheKeyInfo['discriminatorValues']) && ! in_array((string) $discrColumnValue, $cacheKeyInfo['discriminatorValues'], true)) {
                continue;
            }

            // Check if value is null before conversion (because some types convert null to something else)
            $valueIsNull = $value === null;

            // Convert field to a valid PHP value
            if (isset($cacheKeyInfo['type'])) {
                $type  = $cacheKeyInfo['type'];
                $value = $type->convertToPHPValue($value, $this->_platform);
            }

            if ($value !== null && isset($cacheKeyInfo['enumType'])) {
                $originalValue = $value;
                try {
                    $value = $this->buildEnum($originalValue, $cacheKeyInfo['enumType']);
                } catch (ValueError $e) {
                    throw MappingException::invalidEnumValue(
                        $entityName,
                        $cacheKeyInfo['fieldName'],
                        (string) $originalValue,
                        $cacheKeyInfo['enumType'],
                        $e
                    );
                }
            }

            $fieldName = $cacheKeyInfo['fieldName'];

            // Prevent overwrite in case of inherit classes using same property name (See AbstractHydrator)
            if (! isset($data[$fieldName]) || ! $valueIsNull) {
                $data[$fieldName] = $value;
            }
        }

        if (isset($this->_hints[Query::HINT_REFRESH_ENTITY])) {
            $this->registerManaged($this->class, $this->_hints[Query::HINT_REFRESH_ENTITY], $data);
        }

        $uow    = $this->_em->getUnitOfWork();
        $entity = $uow->createEntity($entityName, $data, $this->_hints);

        $result[] = $entity;

        if (isset($this->_hints[Query::HINT_INTERNAL_ITERATION]) && $this->_hints[Query::HINT_INTERNAL_ITERATION]) {
            $this->_uow->hydrationComplete();
        }
    }
}
