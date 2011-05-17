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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Internal\Hydration;

use PDO,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager;

/**
 * Base class for all hydrators. A hydrator is a class that provides some form
 * of transformation of an SQL result set into another structure.
 *
 * @since       2.0
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractHydrator
{
    /** @var ResultSetMapping The ResultSetMapping. */
    protected $_rsm;

    /** @var EntityManager The EntityManager instance. */
    protected $_em;

    /** @var AbstractPlatform The dbms Platform instance */
    protected $_platform;

    /** @var UnitOfWork The UnitOfWork of the associated EntityManager. */
    protected $_uow;

    /** @var array The cache used during row-by-row hydration. */
    protected $_cache = array();

    /** @var Statement The statement that provides the data to hydrate. */
    protected $_stmt;

    /** @var array The query hints. */
    protected $_hints;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractHydrator</tt>.
     *
     * @param Doctrine\ORM\EntityManager $em The EntityManager to use.
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_platform = $em->getConnection()->getDatabasePlatform();
        $this->_uow = $em->getUnitOfWork();
    }

    /**
     * Initiates a row-by-row hydration.
     *
     * @param object $stmt
     * @param object $resultSetMapping
     * @return IterableResult
     */
    public function iterate($stmt, $resultSetMapping, array $hints = array())
    {
        $this->_stmt = $stmt;
        $this->_rsm = $resultSetMapping;
        $this->_hints = $hints;
        $this->_prepare();
        return new IterableResult($this);
    }

    /**
     * Hydrates all rows returned by the passed statement instance at once.
     *
     * @param object $stmt
     * @param object $resultSetMapping
     * @return mixed
     */
    public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
    {
        $this->_stmt = $stmt;
        $this->_rsm = $resultSetMapping;
        $this->_hints = $hints;
        $this->_prepare();
        $result = $this->_hydrateAll();
        $this->_cleanup();
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
            $this->_cleanup();
            return false;
        }
        $result = array();
        $this->_hydrateRow($row, $this->_cache, $result);
        return $result;
    }

    /**
     * Excutes one-time preparation tasks, once each time hydration is started
     * through {@link hydrateAll} or {@link iterate()}.
     */
    protected function _prepare()
    {}

    /**
     * Excutes one-time cleanup tasks at the end of a hydration that was initiated
     * through {@link hydrateAll} or {@link iterate()}.
     */
    protected function _cleanup()
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
     * @param array $data The row data.
     * @param array $cache The cache to use.
     * @param mixed $result The result to fill.
     */
    protected function _hydrateRow(array $data, array &$cache, array &$result)
    {
        throw new HydrationException("_hydrateRow() not implemented by this hydrator.");
    }

    /**
     * Hydrates all rows from the current statement instance at once.
     */
    abstract protected function _hydrateAll();

    /**
     * Processes a row of the result set.
     * Used for identity-based hydration (HYDRATE_OBJECT and HYDRATE_ARRAY).
     * Puts the elements of a result row into a new array, grouped by the class
     * they belong to. The column names in the result set are mapped to their
     * field names during this procedure as well as any necessary conversions on
     * the values applied.
     *
     * @return array  An array with all the fields (name => value) of the data row,
     *                grouped by their component alias.
     */
    protected function _gatherRowData(array $data, array &$cache, array &$id, array &$nonemptyComponents)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if (isset($this->_rsm->scalarMappings[$key])) {
                    $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                    $cache[$key]['isScalar'] = true;
                } else if (isset($this->_rsm->fieldMappings[$key])) {
                    $fieldName = $this->_rsm->fieldMappings[$key];
                    $classMetadata = $this->_em->getClassMetadata($this->_rsm->declaringClasses[$key]);
                    $cache[$key]['fieldName'] = $fieldName;
                    $cache[$key]['type'] = Type::getType($classMetadata->fieldMappings[$fieldName]['type']);
                    $cache[$key]['isIdentifier'] = $classMetadata->isIdentifier($fieldName);
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                } else if (!isset($this->_rsm->metaMappings[$key])) {
                    // this column is a left over, maybe from a LIMIT query hack for example in Oracle or DB2
                    // maybe from an additional column that has not been defined in a NativeQuery ResultSetMapping.
                    continue;
                } else {
                    // Meta column (has meaning in relational schema only, i.e. foreign keys or discriminator columns).
                    $fieldName = $this->_rsm->metaMappings[$key];
                    $cache[$key]['isMetaColumn'] = true;
                    $cache[$key]['fieldName'] = $fieldName;
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                    $classMetadata = $this->_em->getClassMetadata($this->_rsm->aliasMap[$cache[$key]['dqlAlias']]);
                    $cache[$key]['isIdentifier'] = isset($this->_rsm->isIdentifierColumn[$cache[$key]['dqlAlias']][$key]);
                }
            }
            
            if (isset($cache[$key]['isScalar'])) {
                $rowData['scalars'][$cache[$key]['fieldName']] = $value;
                continue;
            }

            $dqlAlias = $cache[$key]['dqlAlias'];

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            if (isset($cache[$key]['isMetaColumn'])) {
                if (!isset($rowData[$dqlAlias][$cache[$key]['fieldName']]) || $value !== null) {
                    $rowData[$dqlAlias][$cache[$key]['fieldName']] = $value;
                }
                continue;
            }
            
            // in an inheritance hierachy the same field could be defined several times.
            // We overwrite this value so long we dont have a non-null value, that value we keep.
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
     * Used for HYDRATE_SCALAR. This is a variant of _gatherRowData() that
     * simply converts column names to field names and properly converts the
     * values according to their types. The resulting row has the same number
     * of elements as before.
     *
     * @param array $data
     * @param array $cache
     * @return array The processed row.
     */
    protected function _gatherScalarRowData(&$data, &$cache)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if (isset($this->_rsm->scalarMappings[$key])) {
                    $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                    $cache[$key]['isScalar'] = true;
                } else if (isset($this->_rsm->fieldMappings[$key])) {
                    $fieldName = $this->_rsm->fieldMappings[$key];
                    $classMetadata = $this->_em->getClassMetadata($this->_rsm->declaringClasses[$key]);
                    $cache[$key]['fieldName'] = $fieldName;
                    $cache[$key]['type'] = Type::getType($classMetadata->fieldMappings[$fieldName]['type']);
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                } else if (!isset($this->_rsm->metaMappings[$key])) {
                    // this column is a left over, maybe from a LIMIT query hack for example in Oracle or DB2
                    // maybe from an additional column that has not been defined in a NativeQuery ResultSetMapping.
                    continue;
                } else {
                    // Meta column (has meaning in relational schema only, i.e. foreign keys or discriminator columns).
                    $cache[$key]['isMetaColumn'] = true;
                    $cache[$key]['fieldName'] = $this->_rsm->metaMappings[$key];
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                }
            }
            
            $fieldName = $cache[$key]['fieldName'];

            if (isset($cache[$key]['isScalar'])) {
                $rowData[$fieldName] = $value;
            } else if (isset($cache[$key]['isMetaColumn'])) {
                $rowData[$cache[$key]['dqlAlias'] . '_' . $fieldName] = $value;
            } else {
                $rowData[$cache[$key]['dqlAlias'] . '_' . $fieldName] = $cache[$key]['type']
                        ->convertToPHPValue($value, $this->_platform);
            }
        }

        return $rowData;
    }
}
