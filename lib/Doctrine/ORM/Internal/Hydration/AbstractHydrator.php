<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker;
use Doctrine\ORM\UnitOfWork;
use Generator;
use LogicException;
use ReflectionClass;

use function array_map;
use function array_merge;
use function count;
use function end;
use function in_array;
use function is_array;

/**
 * Base class for all hydrators. A hydrator is a class that provides some form
 * of transformation of an SQL result set into another structure.
 */
abstract class AbstractHydrator
{
    /**
     * The ResultSetMapping.
     */
    protected ResultSetMapping|null $_rsm = null;

    /**
     * The dbms Platform instance.
     */
    protected AbstractPlatform $_platform;

    /**
     * The UnitOfWork of the associated EntityManager.
     */
    protected UnitOfWork $_uow;

    /**
     * Local ClassMetadata cache to avoid going to the EntityManager all the time.
     *
     * @var array<string, ClassMetadata<object>>
     */
    protected array $_metadataCache = [];

    /**
     * The cache used during row-by-row hydration.
     *
     * @var array<string, mixed[]|null>
     */
    protected array $_cache = [];

    /**
     * The statement that provides the data to hydrate.
     */
    protected Result|null $_stmt = null;

    /**
     * The query hints.
     *
     * @var array<string, mixed>
     */
    protected array $_hints = [];

    protected EntityManagerInterface $_em;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractHydrator</tt>.
     */
    public function __construct(protected EntityManagerInterface $em)
    {
        $this->_em       = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
        $this->_uow      = $em->getUnitOfWork();
    }

    /**
     * Initiates a row-by-row hydration.
     *
     * @psalm-param array<string, mixed> $hints
     *
     * @return Generator<array-key, mixed>
     *
     * @final
     */
    final public function toIterable(Result $stmt, ResultSetMapping $resultSetMapping, array $hints = []): Generator
    {
        $this->_stmt  = $stmt;
        $this->_rsm   = $resultSetMapping;
        $this->_hints = $hints;

        $evm = $this->_em->getEventManager();

        $evm->addEventListener([Events::onClear], $this);

        $this->prepare();

        while (true) {
            $row = $this->statement()->fetchAssociative();

            if ($row === false) {
                $this->cleanup();

                break;
            }

            $result = [];

            $this->hydrateRowData($row, $result);

            $this->cleanupAfterRowIteration();
            if (count($result) === 1) {
                if (count($resultSetMapping->indexByMap) === 0) {
                    yield end($result);
                } else {
                    yield from $result;
                }
            } else {
                yield $result;
            }
        }
    }

    final protected function statement(): Result
    {
        if ($this->_stmt === null) {
            throw new LogicException('Uninitialized _stmt property');
        }

        return $this->_stmt;
    }

    final protected function resultSetMapping(): ResultSetMapping
    {
        if ($this->_rsm === null) {
            throw new LogicException('Uninitialized _rsm property');
        }

        return $this->_rsm;
    }

    /**
     * Hydrates all rows returned by the passed statement instance at once.
     *
     * @psalm-param array<string, string> $hints
     */
    public function hydrateAll(Result $stmt, ResultSetMapping $resultSetMapping, array $hints = []): mixed
    {
        $this->_stmt  = $stmt;
        $this->_rsm   = $resultSetMapping;
        $this->_hints = $hints;

        $this->_em->getEventManager()->addEventListener([Events::onClear], $this);
        $this->prepare();

        try {
            $result = $this->hydrateAllData();
        } finally {
            $this->cleanup();
        }

        return $result;
    }

    /**
     * When executed in a hydrate() loop we have to clear internal state to
     * decrease memory consumption.
     */
    public function onClear(mixed $eventArgs): void
    {
    }

    /**
     * Executes one-time preparation tasks, once each time hydration is started
     * through {@link hydrateAll} or {@link toIterable()}.
     */
    protected function prepare(): void
    {
    }

    /**
     * Executes one-time cleanup tasks at the end of a hydration that was initiated
     * through {@link hydrateAll} or {@link toIterable()}.
     */
    protected function cleanup(): void
    {
        $this->statement()->free();

        $this->_stmt          = null;
        $this->_rsm           = null;
        $this->_cache         = [];
        $this->_metadataCache = [];

        $this
            ->_em
            ->getEventManager()
            ->removeEventListener([Events::onClear], $this);
    }

    protected function cleanupAfterRowIteration(): void
    {
    }

    /**
     * Hydrates a single row from the current statement instance.
     *
     * Template method.
     *
     * @param mixed[] $row    The row data.
     * @param mixed[] $result The result to fill.
     *
     * @throws HydrationException
     */
    protected function hydrateRowData(array $row, array &$result): void
    {
        throw new HydrationException('hydrateRowData() not implemented by this hydrator.');
    }

    /**
     * Hydrates all rows from the current statement instance at once.
     */
    abstract protected function hydrateAllData(): mixed;

    /**
     * Processes a row of the result set.
     *
     * Used for identity-based hydration (HYDRATE_OBJECT and HYDRATE_ARRAY).
     * Puts the elements of a result row into a new array, grouped by the dql alias
     * they belong to. The column names in the result set are mapped to their
     * field names during this procedure as well as any necessary conversions on
     * the values applied. Scalar values are kept in a specific key 'scalars'.
     *
     * @param mixed[] $data SQL Result Row.
     * @psalm-param array<string, string> $id                 Dql-Alias => ID-Hash.
     * @psalm-param array<string, bool>   $nonemptyComponents Does this DQL-Alias has at least one non NULL value?
     *
     * @return array<string, array<string, mixed>> An array with all the fields
     *                                             (name => value) of the data
     *                                             row, grouped by their
     *                                             component alias.
     * @psalm-return array{
     *                   data: array<array-key, array>,
     *                   newObjects?: array<array-key, array{
     *                       class: mixed,
     *                       args?: array
     *                   }>,
     *                   scalars?: array
     *               }
     */
    protected function gatherRowData(array $data, array &$id, array &$nonemptyComponents): array
    {
        $rowData = ['data' => []];

        foreach ($data as $key => $value) {
            $cacheKeyInfo = $this->hydrateColumnInfo($key);
            if ($cacheKeyInfo === null) {
                continue;
            }

            $fieldName = $cacheKeyInfo['fieldName'];

            switch (true) {
                case isset($cacheKeyInfo['isNewObjectParameter']):
                    $argIndex = $cacheKeyInfo['argIndex'];
                    $objIndex = $cacheKeyInfo['objIndex'];
                    $type     = $cacheKeyInfo['type'];
                    $value    = $type->convertToPHPValue($value, $this->_platform);

                    $rowData['newObjects'][$objIndex]['class']           = $cacheKeyInfo['class'];
                    $rowData['newObjects'][$objIndex]['args'][$argIndex] = $value;
                    break;

                case isset($cacheKeyInfo['isScalar']):
                    $type  = $cacheKeyInfo['type'];
                    $value = $type->convertToPHPValue($value, $this->_platform);

                    // Reimplement ReflectionEnumProperty code
                    if ($value !== null && isset($cacheKeyInfo['enumType'])) {
                        $enumType = $cacheKeyInfo['enumType'];
                        if (is_array($value)) {
                            $value = array_map(static fn ($value): BackedEnum => $enumType::from($value), $value);
                        } else {
                            $value = $enumType::from($value);
                        }
                    }

                    $rowData['scalars'][$fieldName] = $value;

                    break;

                //case (isset($cacheKeyInfo['isMetaColumn'])):
                default:
                    $dqlAlias = $cacheKeyInfo['dqlAlias'];
                    $type     = $cacheKeyInfo['type'];

                    // If there are field name collisions in the child class, then we need
                    // to only hydrate if we are looking at the correct discriminator value
                    if (
                        isset($cacheKeyInfo['discriminatorColumn'], $data[$cacheKeyInfo['discriminatorColumn']])
                        && ! in_array((string) $data[$cacheKeyInfo['discriminatorColumn']], $cacheKeyInfo['discriminatorValues'], true)
                    ) {
                        break;
                    }

                    // in an inheritance hierarchy the same field could be defined several times.
                    // We overwrite this value so long we don't have a non-null value, that value we keep.
                    // Per definition it cannot be that a field is defined several times and has several values.
                    if (isset($rowData['data'][$dqlAlias][$fieldName])) {
                        break;
                    }

                    $rowData['data'][$dqlAlias][$fieldName] = $type
                        ? $type->convertToPHPValue($value, $this->_platform)
                        : $value;

                    if ($cacheKeyInfo['isIdentifier'] && $value !== null) {
                        $id[$dqlAlias]                .= '|' . $value;
                        $nonemptyComponents[$dqlAlias] = true;
                    }

                    break;
            }
        }

        return $rowData;
    }

    /**
     * Processes a row of the result set.
     *
     * Used for HYDRATE_SCALAR. This is a variant of _gatherRowData() that
     * simply converts column names to field names and properly converts the
     * values according to their types. The resulting row has the same number
     * of elements as before.
     *
     * @param mixed[] $data
     * @psalm-param array<string, mixed> $data
     *
     * @return mixed[] The processed row.
     * @psalm-return array<string, mixed>
     */
    protected function gatherScalarRowData(array &$data): array
    {
        $rowData = [];

        foreach ($data as $key => $value) {
            $cacheKeyInfo = $this->hydrateColumnInfo($key);
            if ($cacheKeyInfo === null) {
                continue;
            }

            $fieldName = $cacheKeyInfo['fieldName'];

            // WARNING: BC break! We know this is the desired behavior to type convert values, but this
            // erroneous behavior exists since 2.0 and we're forced to keep compatibility.
            if (! isset($cacheKeyInfo['isScalar'])) {
                $type  = $cacheKeyInfo['type'];
                $value = $type ? $type->convertToPHPValue($value, $this->_platform) : $value;

                $fieldName = $cacheKeyInfo['dqlAlias'] . '_' . $fieldName;
            }

            $rowData[$fieldName] = $value;
        }

        return $rowData;
    }

    /**
     * Retrieve column information from ResultSetMapping.
     *
     * @param string $key Column name
     *
     * @return mixed[]|null
     * @psalm-return array<string, mixed>|null
     */
    protected function hydrateColumnInfo(string $key): array|null
    {
        if (isset($this->_cache[$key])) {
            return $this->_cache[$key];
        }

        switch (true) {
            // NOTE: Most of the times it's a field mapping, so keep it first!!!
            case isset($this->_rsm->fieldMappings[$key]):
                $classMetadata = $this->getClassMetadata($this->_rsm->declaringClasses[$key]);
                $fieldName     = $this->_rsm->fieldMappings[$key];
                $fieldMapping  = $classMetadata->fieldMappings[$fieldName];
                $ownerMap      = $this->_rsm->columnOwnerMap[$key];
                $columnInfo    = [
                    'isIdentifier' => in_array($fieldName, $classMetadata->identifier, true),
                    'fieldName'    => $fieldName,
                    'type'         => Type::getType($fieldMapping['type']),
                    'dqlAlias'     => $ownerMap,
                ];

                // the current discriminator value must be saved in order to disambiguate fields hydration,
                // should there be field name collisions
                if ($classMetadata->parentClasses && isset($this->_rsm->discriminatorColumns[$ownerMap])) {
                    return $this->_cache[$key] = array_merge(
                        $columnInfo,
                        [
                            'discriminatorColumn' => $this->_rsm->discriminatorColumns[$ownerMap],
                            'discriminatorValue'  => $classMetadata->discriminatorValue,
                            'discriminatorValues' => $this->getDiscriminatorValues($classMetadata),
                        ],
                    );
                }

                return $this->_cache[$key] = $columnInfo;

            case isset($this->_rsm->newObjectMappings[$key]):
                // WARNING: A NEW object is also a scalar, so it must be declared before!
                $mapping = $this->_rsm->newObjectMappings[$key];

                return $this->_cache[$key] = [
                    'isScalar'             => true,
                    'isNewObjectParameter' => true,
                    'fieldName'            => $this->_rsm->scalarMappings[$key],
                    'type'                 => Type::getType($this->_rsm->typeMappings[$key]),
                    'argIndex'             => $mapping['argIndex'],
                    'objIndex'             => $mapping['objIndex'],
                    'class'                => new ReflectionClass($mapping['className']),
                ];

            case isset($this->_rsm->scalarMappings[$key], $this->_hints[LimitSubqueryWalker::FORCE_DBAL_TYPE_CONVERSION]):
                return $this->_cache[$key] = [
                    'fieldName' => $this->_rsm->scalarMappings[$key],
                    'type'      => Type::getType($this->_rsm->typeMappings[$key]),
                    'dqlAlias'  => '',
                    'enumType'  => $this->_rsm->enumMappings[$key] ?? null,
                ];

            case isset($this->_rsm->scalarMappings[$key]):
                return $this->_cache[$key] = [
                    'isScalar'  => true,
                    'fieldName' => $this->_rsm->scalarMappings[$key],
                    'type'      => Type::getType($this->_rsm->typeMappings[$key]),
                    'enumType'  => $this->_rsm->enumMappings[$key] ?? null,
                ];

            case isset($this->_rsm->metaMappings[$key]):
                // Meta column (has meaning in relational schema only, i.e. foreign keys or discriminator columns).
                $fieldName = $this->_rsm->metaMappings[$key];
                $dqlAlias  = $this->_rsm->columnOwnerMap[$key];
                $type      = isset($this->_rsm->typeMappings[$key])
                    ? Type::getType($this->_rsm->typeMappings[$key])
                    : null;

                // Cache metadata fetch
                $this->getClassMetadata($this->_rsm->aliasMap[$dqlAlias]);

                return $this->_cache[$key] = [
                    'isIdentifier' => isset($this->_rsm->isIdentifierColumn[$dqlAlias][$key]),
                    'isMetaColumn' => true,
                    'fieldName'    => $fieldName,
                    'type'         => $type,
                    'dqlAlias'     => $dqlAlias,
                ];
        }

        // this column is a left over, maybe from a LIMIT query hack for example in Oracle or DB2
        // maybe from an additional column that has not been defined in a NativeQuery ResultSetMapping.
        return null;
    }

    /**
     * @return string[]
     * @psalm-return non-empty-list<string>
     */
    private function getDiscriminatorValues(ClassMetadata $classMetadata): array
    {
        $values = array_map(
            fn (string $subClass): string => (string) $this->getClassMetadata($subClass)->discriminatorValue,
            $classMetadata->subClasses,
        );

        $values[] = (string) $classMetadata->discriminatorValue;

        return $values;
    }

    /**
     * Retrieve ClassMetadata associated to entity class name.
     */
    protected function getClassMetadata(string $className): ClassMetadata
    {
        if (! isset($this->_metadataCache[$className])) {
            $this->_metadataCache[$className] = $this->_em->getClassMetadata($className);
        }

        return $this->_metadataCache[$className];
    }

    /**
     * Register entity as managed in UnitOfWork.
     *
     * @param mixed[] $data
     *
     * @todo The "$id" generation is the same of UnitOfWork#createEntity. Remove this duplication somehow
     */
    protected function registerManaged(ClassMetadata $class, object $entity, array $data): void
    {
        if ($class->isIdentifierComposite) {
            $id = [];

            foreach ($class->identifier as $fieldName) {
                $id[$fieldName] = isset($class->associationMappings[$fieldName])
                    ? $data[$class->associationMappings[$fieldName]['joinColumns'][0]['name']]
                    : $data[$fieldName];
            }
        } else {
            $fieldName = $class->identifier[0];
            $id        = [
                $fieldName => isset($class->associationMappings[$fieldName])
                    ? $data[$class->associationMappings[$fieldName]['joinColumns'][0]['name']]
                    : $data[$fieldName],
            ];
        }

        $this->_em->getUnitOfWork()->registerManaged($entity, $id, $data);
    }
}
