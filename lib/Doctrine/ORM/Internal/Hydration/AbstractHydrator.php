<?php
/*
 *  $Id: Hydrate.php 3192 2007-11-19 17:55:23Z romanb $
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

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\DBAL\Types\Type;
use Doctrine\Common\DoctrineException;
use \PDO;

/**
 * Base class for all hydrators. A hydrator is a class that provides some form
 * of transformation of an SQL result set into another structure.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractHydrator
{
    /** The ResultSetMapping. */
    protected $_rsm;

    /** @var EntityManager The EntityManager instance. */
    protected $_em;

    /** @var UnitOfWork The UnitOfWork of the associated EntityManager. */
    protected $_uow;

    /** @var array The cache used during row-by-row hydration. */
    protected $_cache = array();

    /** @var Statement The statement that provides the data to hydrate. */
    protected $_stmt;

    /**
     * Initializes a new instance of a class derived from <tt>AbstractHydrator</tt>.
     *
     * @param Doctrine\ORM\EntityManager $em The EntityManager to use.
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->_em = $em;
        $this->_uow = $em->getUnitOfWork();
    }

    /**
     * Initiates a row-by-row hydration.
     *
     * @param object $stmt
     * @param object $resultSetMapping
     * @return IterableResult
     */
    public function iterate($stmt, $resultSetMapping)
    {
        $this->_stmt = $stmt;
        $this->_rsm = $resultSetMapping;
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
    public function hydrateAll($stmt, $resultSetMapping)
    {
        $this->_stmt = $stmt;
        $this->_rsm = $resultSetMapping;
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
        $result = $this->_getRowContainer();
        $this->_hydrateRow($row, $this->_cache, $result);
        return $result;
    }

    /**
     * Excutes one-time preparation tasks once each time hydration is started
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
    protected function _hydrateRow(array &$data, array &$cache, &$result)
    {
        throw new DoctrineException("_hydrateRow() not implemented for this hydrator.");
    }

    /**
     * Hydrates all rows from the current statement instance at once.
     */
    abstract protected function _hydrateAll();

    /**
     * Gets the row container used during row-by-row hydration through {@link iterate()}.
     */
    abstract protected function _getRowContainer();

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
    protected function _gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if (isset($this->_rsm->ignoredColumns[$key])) {
                    $cache[$key] = false;
                } else if (isset($this->_rsm->scalarMappings[$key])) {
                    $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                    $cache[$key]['isScalar'] = true;
                } else if (isset($this->_rsm->fieldMappings[$key])) {
                    $classMetadata = $this->_em->getClassMetadata($this->_rsm->getOwningClass($key));
                    $fieldName = $this->_rsm->fieldMappings[$key];
                    $classMetadata = $this->_lookupDeclaringClass($classMetadata, $fieldName);
                    $cache[$key]['fieldName'] = $fieldName;
                    $cache[$key]['isScalar'] = false;
                    $cache[$key]['type'] = Type::getType($classMetadata->getTypeOfField($fieldName));
                    $cache[$key]['isIdentifier'] = $classMetadata->isIdentifier($fieldName);
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                } else {
                    // Discriminator column
                    $cache[$key]['isDiscriminator'] = true;
                    $cache[$key]['isScalar'] = false;
                    $cache[$key]['fieldName'] = $key;
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                }
            }

            if ($cache[$key]['isScalar']) {
                $rowData['scalars'][$cache[$key]['fieldName']] = $value;
                continue;
            }

            $dqlAlias = $cache[$key]['dqlAlias'];

            if (isset($cache[$key]['isDiscriminator'])) {
                $rowData[$dqlAlias][$cache[$key]['fieldName']] = $value;
                continue;
            }

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            $rowData[$dqlAlias][$cache[$key]['fieldName']] = $cache[$key]['type']->convertToPHPValue($value);

            if ( ! isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
            }

            /* TODO: Consider this instead of the above 4 lines. */
            /*if ($value !== null) {
                $rowData[$dqlAlias][$fieldName] = $cache[$key]['type']->convertToPHPValue($value);
            }*/
        }

        return $rowData;
    }

    /**
     * Processes a row of the result set.
     * Used for HYDRATE_SCALAR. This is a variant of _gatherRowData() that
     * simply converts column names to field names and properly prepares the
     * values. The resulting row has the same number of elements as before.
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
                if (isset($this->_rsm->ignoredColumns[$key])) {
                    $cache[$key] = false;
                    continue;
                } else if (isset($this->_rsm->scalarMappings[$key])) {
                    $cache[$key]['fieldName'] = $this->_rsm->scalarMappings[$key];
                    $cache[$key]['isScalar'] = true;
                } else {
                    $classMetadata = $this->_em->getClassMetadata($this->_rsm->getOwningClass($key));
                    $fieldName = $this->_rsm->fieldMappings[$key];
                    $classMetadata = $this->_lookupDeclaringClass($classMetadata, $fieldName);
                    $cache[$key]['fieldName'] = $fieldName;
                    $cache[$key]['isScalar'] = false;
                    $cache[$key]['type'] = Type::getType($classMetadata->getTypeOfField($fieldName));
                    $cache[$key]['dqlAlias'] = $this->_rsm->columnOwnerMap[$key];
                }
            }
            
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isScalar']) {
                $rowData[/*$dqlAlias . '_' . */$fieldName] = $value;
            } else {
                $dqlAlias = $cache[$key]['dqlAlias'];
                $rowData[$dqlAlias . '_' . $fieldName] = $cache[$key]['type']->convertToPHPValue($value);
            }
        }

        return $rowData;
    }

    /**
     * Gets the custom field used for indexing for the specified DQL alias.
     *
     * @return string  The field name of the field used for indexing or NULL
     *                 if the component does not use any custom field indices.
     */
    protected function _getCustomIndexField($alias)
    {
        return isset($this->_rsm->indexByMap[$alias]) ? $this->_rsm->indexByMap[$alias] : null;
    }

    /**
     * Looks up the field name for a (lowercased) column name.
     *
     * This is mostly used during hydration, because we want to make the
     * conversion to field names while iterating over the result set for best
     * performance. By doing this at that point, we can avoid re-iterating over
     * the data just to convert the column names to field names.
     *
     * However, when this is happening, we don't know the real
     * class name to instantiate yet (the row data may target a sub-type), hence
     * this method looks up the field name in the subclass mappings if it's not
     * found on this class mapping.
     * This lookup on subclasses is costly but happens only *once* for a column
     * during hydration because the hydrator caches effectively.
     *
     * @return string  The field name.
     * @throws DoctrineException If the field name could not be found.
     */
    private function _lookupDeclaringClass($class, $fieldName)
    {
        if (isset($class->reflFields[$fieldName])) {
            return $class;
        }
        
        foreach ($class->subClasses as $subClass) {
            $subClassMetadata = $this->_em->getClassMetadata($subClass);
            if ($subClassMetadata->hasField($fieldName)) {
                return $subClassMetadata;
            }
        }

        throw DoctrineException::updateMe("No owner found for field '$fieldName' during hydration.");
    }
}