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

namespace Doctrine\ORM\Internal\Hydration;

use PDO;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Base class for all hydrators. A hydrator is a class that provides some form
 * of transformation of an SQL result set into another structure.
 *
 * @since  2.0
 * @author Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanoc@hotmail.com>
 */
abstract class AbstractHydrator
{
    /**
     * The ResultSetMapping.
     *
     * @var \Doctrine\ORM\Query\ResultSetMapping
     */
    protected $_rsm;

    /**
     * The EntityManager instance.
     *
     * @var EntityManager
     */
    protected $_em;

    /**
     * The dbms Platform instance.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $_platform;

    /**
     * The UnitOfWork of the associated EntityManager.
     *
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $_uow;

    /**
     * The cache used during row-by-row hydration.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * The statement that provides the data to hydrate.
     *
     * @var \Doctrine\DBAL\Driver\Statement
     */
    protected $_stmt;

    /**
     * The query hints.
     *
     * @var array
     */
    protected $_hints;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractHydrator</tt>.
     *
     * @param \Doctrine\ORM\EntityManager $em The EntityManager to use.
     */
    public function __construct(EntityManager $em)
    {
        $this->_em       = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
        $this->_uow      = $em->getUnitOfWork();
    }

    /**
     * Initiates a row-by-row hydration.
     *
     * @param object $stmt
     * @param object $resultSetMapping
     * @param array  $hints
     *
     * @return IterableResult
     */
    public function iterate($stmt, $resultSetMapping, array $hints = array())
    {
        $this->_stmt  = $stmt;
        $this->_rsm   = $resultSetMapping;
        $this->_hints = $hints;

        $evm = $this->_em->getEventManager();
        $evm->addEventListener(array(Events::onClear), $this);

        $this->prepare();

        return new IterableResult($this);
    }

    /**
     * Hydrates all rows returned by the passed statement instance at once.
     *
     * @param object $stmt
     * @param object $resultSetMapping
     * @param array  $hints
     *
     * @return array
     */
    public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
    {
        $this->_stmt  = $stmt;
        $this->_rsm   = $resultSetMapping;
        $this->_hints = $hints;

        $this->prepare();

        $result = $this->hydrateAllData();

        $this->cleanup();

        return $result;
    }

    /**
     * Hydrates a single row returned by the current statement instance during
     * row-by-row hydration with {@link iterate()}.
     *
     * @return mixed
     */
    public function hydrateRow()
    {
        $row = $this->_stmt->fetch(PDO::FETCH_ASSOC);

        if ( ! $row) {
            $this->cleanup();

            return false;
        }

        $result = array();

        $this->hydrateRowData($row, $this->_cache, $result);

        return $result;
    }

    /**
     * Executes one-time preparation tasks, once each time hydration is started
     * through {@link hydrateAll} or {@link iterate()}.
     *
     * @return void
     */
    protected function prepare()
    {
    }

    /**
     * Executes one-time cleanup tasks at the end of a hydration that was initiated
     * through {@link hydrateAll} or {@link iterate()}.
     *
     * @return void
     */
    protected function cleanup()
    {
        $this->_rsm = null;

        $this->_stmt->closeCursor();
        $this->_stmt = null;
    }

    /**
     * Hydrates a single row from the current statement instance.
     *
     * Template method.
     *
     * @param array $data   The row data.
     * @param array $cache  The cache to use.
     * @param array $result The result to fill.
     *
     * @return void
     *
     * @throws HydrationException
     */
    protected function hydrateRowData(array $data, array &$cache, array &$result)
    {
        throw new HydrationException("hydrateRowData() not implemented by this hydrator.");
    }

    /**
     * Hydrates all rows from the current statement instance at once.
     *
     * @return array
     */
    abstract protected function hydrateAllData();

    /**
     * Processes a row of the result set.
     *
     * Used for identity-based hydration (HYDRATE_OBJECT and HYDRATE_ARRAY).
     * Puts the elements of a result row into a new array, grouped by the dql alias
     * they belong to. The column names in the result set are mapped to their
     * field names during this procedure as well as any necessary conversions on
     * the values applied. Scalar values are kept in a specific key 'scalars'.
     *
     * @param array  $data               SQL Result Row.
     * @param array &$cache              Cache for column to field result information.
     * @param array &$id                 Dql-Alias => ID-Hash.
     * @param array &$nonemptyComponents Does this DQL-Alias has at least one non NULL value?
     *
     * @return array  An array with all the fields (name => value) of the data row,
     *                grouped by their component alias.
     */
    protected function gatherRowData(array $data, array &$cache, array &$id, array &$nonemptyComponents)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                switch (true) {
                    // NOTE: Most of the times it's a field mapping, so keep it first!!!
                    case (isset($this->_rsm->fieldMappings[$key])):
                        $fieldName     = $this->_rsm->fieldMappings[$key];
                        $classMetadata = $this->_em->getClassMetadata($this->_rsm->declaringClasses[$key]);

                        $cache[$key]['fieldName']    = $fieldName;
                        $cache[$key]['type']         = Type::getType($classMetadata->fieldMappings[$fieldName]['type']);
                        $cache[$key]['isIdentifier'] = $classMetadata->isIdentifier($fieldName);
                        $cache[$key]['dqlAlias']     = $this->_rsm->columnOwnerMap[$key];
                        break;

                    case (isset($this->_rsm->scalarMappings[$key])):
                        $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                        $cache[$key]['type']      = Type::getType($this->_rsm->typeMappings[$key]);
                        $cache[$key]['isScalar']  = true;
                        break;

                    case (isset($this->_rsm->metaMappings[$key])):
                        // Meta column (has meaning in relational schema only, i.e. foreign keys or discriminator columns).
                        $fieldName     = $this->_rsm->metaMappings[$key];
                        $classMetadata = $this->_em->getClassMetadata($this->_rsm->aliasMap[$this->_rsm->columnOwnerMap[$key]]);

                        $cache[$key]['isMetaColumn'] = true;
                        $cache[$key]['fieldName']    = $fieldName;
                        $cache[$key]['dqlAlias']     = $this->_rsm->columnOwnerMap[$key];
                        $cache[$key]['isIdentifier'] = isset($this->_rsm->isIdentifierColumn[$cache[$key]['dqlAlias']][$key]);
                        break;

                    default:
                        // this column is a left over, maybe from a LIMIT query hack for example in Oracle or DB2
                        // maybe from an additional column that has not been defined in a NativeQuery ResultSetMapping.
                        continue 2;
                }

                if (isset($this->_rsm->newObjectMappings[$key])) {
                    $mapping = $this->_rsm->newObjectMappings[$key];

                    $cache[$key]['isNewObjectParameter'] = true;
                    $cache[$key]['argIndex']             = $mapping['argIndex'];
                    $cache[$key]['objIndex']             = $mapping['objIndex'];
                    $cache[$key]['class']                = new \ReflectionClass($mapping['className']);
                }
            }

            if (isset($cache[$key]['isNewObjectParameter'])) {
                $class    = $cache[$key]['class'];
                $argIndex = $cache[$key]['argIndex'];
                $objIndex = $cache[$key]['objIndex'];
                $value    = $cache[$key]['type']->convertToPHPValue($value, $this->_platform);

                $rowData['newObjects'][$objIndex]['class']           = $class;
                $rowData['newObjects'][$objIndex]['args'][$argIndex] = $value;
            }

            if (isset($cache[$key]['isScalar'])) {
                $value = $cache[$key]['type']->convertToPHPValue($value, $this->_platform);

                $rowData['scalars'][$cache[$key]['fieldName']] = $value;

                continue;
            }

            $dqlAlias = $cache[$key]['dqlAlias'];

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            if (isset($cache[$key]['isMetaColumn'])) {
                if ( ! isset($rowData[$dqlAlias][$cache[$key]['fieldName']]) && $value !== null) {
                    $rowData[$dqlAlias][$cache[$key]['fieldName']] = $value;
                    if ($cache[$key]['isIdentifier']) {
                        $nonemptyComponents[$dqlAlias] = true;
                    }
                }

                continue;
            }

            // in an inheritance hierarchy the same field could be defined several times.
            // We overwrite this value so long we don't have a non-null value, that value we keep.
            // Per definition it cannot be that a field is defined several times and has several values.
            if (isset($rowData[$dqlAlias][$cache[$key]['fieldName']]) && $value === null) {
                continue;
            }

            $rowData[$dqlAlias][$cache[$key]['fieldName']] = $cache[$key]['type']->convertToPHPValue($value, $this->_platform);

            if ( ! isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
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
     * @param array $data
     * @param array $cache
     *
     * @return array The processed row.
     */
    protected function gatherScalarRowData(&$data, &$cache)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                switch (true) {
                    // NOTE: During scalar hydration, most of the times it's a scalar mapping, keep it first!!!
                    case (isset($this->_rsm->scalarMappings[$key])):
                        $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                        $cache[$key]['isScalar']  = true;
                        break;

                    case (isset($this->_rsm->fieldMappings[$key])):
                        $fieldName     = $this->_rsm->fieldMappings[$key];
                        $classMetadata = $this->_em->getClassMetadata($this->_rsm->declaringClasses[$key]);

                        $cache[$key]['fieldName'] = $fieldName;
                        $cache[$key]['type']      = Type::getType($classMetadata->fieldMappings[$fieldName]['type']);
                        $cache[$key]['dqlAlias']  = $this->_rsm->columnOwnerMap[$key];
                        break;

                    case (isset($this->_rsm->metaMappings[$key])):
                        // Meta column (has meaning in relational schema only, i.e. foreign keys or discriminator columns).
                        $cache[$key]['isMetaColumn'] = true;
                        $cache[$key]['fieldName']    = $this->_rsm->metaMappings[$key];
                        $cache[$key]['dqlAlias']     = $this->_rsm->columnOwnerMap[$key];
                        break;

                    default:
                        // this column is a left over, maybe from a LIMIT query hack for example in Oracle or DB2
                        // maybe from an additional column that has not been defined in a NativeQuery ResultSetMapping.
                        continue 2;
                }
            }

            $fieldName = $cache[$key]['fieldName'];

            switch (true) {
                case (isset($cache[$key]['isScalar'])):
                    $rowData[$fieldName] = $value;
                    break;

                case (isset($cache[$key]['isMetaColumn'])):
                    $rowData[$cache[$key]['dqlAlias'] . '_' . $fieldName] = $value;
                    break;

                default:
                    $value = $cache[$key]['type']->convertToPHPValue($value, $this->_platform);

                    $rowData[$cache[$key]['dqlAlias'] . '_' . $fieldName] = $value;
            }
        }

        return $rowData;
    }

    /**
     * Register entity as managed in UnitOfWork.
     *
     * @param ClassMetadata $class
     * @param object        $entity
     * @param array         $data
     *
     * @return void
     *
     * @todo The "$id" generation is the same of UnitOfWork#createEntity. Remove this duplication somehow
     */
    protected function registerManaged(ClassMetadata $class, $entity, array $data)
    {
        if ($class->isIdentifierComposite) {
            $id = array();
            foreach ($class->identifier as $fieldName) {
                if (isset($class->associationMappings[$fieldName])) {
                    $id[$fieldName] = $data[$class->associationMappings[$fieldName]['joinColumns'][0]['name']];
                } else {
                    $id[$fieldName] = $data[$fieldName];
                }
            }
        } else {
            if (isset($class->associationMappings[$class->identifier[0]])) {
                $id = array($class->identifier[0] => $data[$class->associationMappings[$class->identifier[0]]['joinColumns'][0]['name']]);
            } else {
                $id = array($class->identifier[0] => $data[$class->identifier[0]]);
            }
        }

        $this->_em->getUnitOfWork()->registerManaged($entity, $id, $data);
    }

    /**
     * When executed in a hydrate() loop we have to clear internal state to
     * decrease memory consumption.
     *
     * @param mixed $eventArgs
     *
     * @return void
     */
    public function onClear($eventArgs)
    {
    }
}
