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

use \PDO;

/**
 * Base class for all hydrators (ok, we got only 1 currently).
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
    /**
     * @var array $_queryComponents
     *
     * Two dimensional array containing the map for query aliases. Main keys are component aliases.
     *
     * metadata  ClassMetadata object associated with given alias.
     * relation Relation object owned by the parent.
     * parent   Alias of the parent.
     * agg      Aggregates of this component.
     * map      Name of the column / aggregate value this component is mapped to in a collection.
     */
    protected $_queryComponents = array();

    /** @var array Table alias map. Keys are SQL aliases and values DQL aliases. */
    protected $_tableAliases = array();

    /** @var EntityManager The EntityManager instance. */
    protected $_em;

    /** @var UnitOfWork The UnitOfWork of the associated EntityManager. */
    protected $_uow;

    /** @var array The cache used during row-by-row hydration. */
    protected $_cache = array();

    /** @var Statement The statement that provides the data to hydrate. */
    protected $_stmt;

    /** @var object The ParserResult instance that holds the necessary information for hydration. */
    protected $_parserResult;

    /**
     * Initializes a new instance of a class derived from AbstractHydrator.
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
     * @param object $parserResult
     * @return IterableResult
     */
    public function iterate($stmt, $parserResult)
    {
        $this->_stmt = $stmt;
        $this->_prepare($parserResult);
        return new IterableResult($this);
    }

    /**
     * Hydrates all rows returned by the passed statement instance at once.
     *
     * @param object $stmt
     * @param object $parserResult
     * @return mixed
     */
    public function hydrateAll($stmt, $parserResult)
    {
        $this->_stmt = $stmt;
        $this->_prepare($parserResult);
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
     *
     * @param object $parserResult
     */
    protected function _prepare($parserResult)
    {
        $this->_queryComponents = $parserResult->getQueryComponents();
        $this->_tableAliases = $parserResult->getTableAliasMap();
        $this->_parserResult = $parserResult;
    }

    /**
     * Excutes one-time cleanup tasks at the end of a hydration that was initiated
     * through {@link hydrateAll} or {@link iterate()}.
     */
    protected function _cleanup()
    {
        $this->_parserResult = null;
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
    {}

    /**
     * Hydrates all rows from the current statement instance at once.
     *
     * @param object $parserResult
     */
    abstract protected function _hydrateAll();

    /**
     * Gets the row container used during row-by-row hydration through {@link iterate()}.
     */
    abstract protected function _getRowContainer();

    /**
     * Processes a row of the result set.
     * Used for identity hydration (HYDRATE_IDENTITY_OBJECT and HYDRATE_IDENTITY_ARRAY).
     * Puts the elements of a result row into a new array, grouped by the class
     * they belong to. The column names in the result set are mapped to their
     * field names during this procedure as well as any necessary conversions on
     * the values applied.
     *
     * @return array  An array with all the fields (name => value) of the data row,
     *                grouped by their component (alias).
     */
    protected function _gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = array();

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if ( ! isset($cache[$key])) {
                if ($this->_isIgnoredName($key)) continue;

                // Cache general information like the column name <-> field name mapping
                $e = explode(\Doctrine\ORM\Query\ParserRule::SQLALIAS_SEPARATOR, $key);
                $columnName = array_pop($e);
                $cache[$key]['dqlAlias'] = $this->_tableAliases[
                        implode(\Doctrine\ORM\Query\ParserRule::SQLALIAS_SEPARATOR, $e)
                        ];
                $classMetadata = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                    $cache[$key]['isScalar'] = true;
                } else {
                    $fieldName = $this->_lookupFieldName($classMetadata, $columnName);
                    $cache[$key]['isScalar'] = false;
                    $cache[$key]['type'] = $classMetadata->getTypeOfColumn($columnName);
                }

                $cache[$key]['fieldName'] = $fieldName;

                // Cache identifier information
                $cache[$key]['isIdentifier'] = $classMetadata->isIdentifier($fieldName);
                /*if ($classMetadata->isIdentifier($fieldName)) {
                    $cache[$key]['isIdentifier'] = true;
                } else {
                    $cache[$key]['isIdentifier'] = false;
                }*/
            }

            $class = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
            $dqlAlias = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isScalar']) {
                $rowData['scalars'][$fieldName] = $value;
                continue;
            }

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            $rowData[$dqlAlias][$fieldName] = $cache[$key]['type']->convertToPHPValue($value);

            if ( ! isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
            }
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
                if ($this->_isIgnoredName($key)) continue;

                // cache general information like the column name <-> field name mapping
                $e = explode(\Doctrine\ORM\Query\ParserRule::SQLALIAS_SEPARATOR, $key);
                $columnName = array_pop($e);
                $cache[$key]['dqlAlias'] = $this->_tableAliases[
                        implode(\Doctrine\ORM\Query\ParserRule::SQLALIAS_SEPARATOR, $e)
                        ];
                $classMetadata = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
                // check whether it's an aggregate value or a regular field
                if (isset($this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName])) {
                    $fieldName = $this->_queryComponents[$cache[$key]['dqlAlias']]['agg'][$columnName];
                    $cache[$key]['isScalar'] = true;
                } else {
                    $fieldName = $this->_lookupFieldName($classMetadata, $columnName);
                    $cache[$key]['isScalar'] = false;
                    // cache type information
                    $cache[$key]['type'] = $classMetadata->getTypeOfColumn($columnName);
                }
                $cache[$key]['fieldName'] = $fieldName;
            }

            $class = $this->_queryComponents[$cache[$key]['dqlAlias']]['metadata'];
            $dqlAlias = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];

            if ($cache[$key]['isScalar']) {
                $rowData[$dqlAlias . '_' . $fieldName] = $value;
            } else {
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
        return isset($this->_queryComponents[$alias]['map']) ? $this->_queryComponents[$alias]['map'] : null;
    }

    /**
     * Checks whether a name is ignored. Used during result set parsing to skip
     * certain elements in the result set that do not have any meaning for the result.
     * (I.e. ORACLE limit/offset emulation adds doctrine_rownum to the result set).
     *
     * @param string $name
     * @return boolean
     */
    private function _isIgnoredName($name)
    {
        return $name == 'doctrine_rownum';
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
    private function _lookupFieldName($class, $lcColumnName)
    {
        if ($class->hasLowerColumn($lcColumnName)) {
            return $class->getFieldNameForLowerColumnName($lcColumnName);
        }

        foreach ($class->getSubclasses() as $subClass) {
            $subClassMetadata = Doctrine_ORM_Mapping_ClassMetadataFactory::getInstance()
                    ->getMetadataFor($subClass);
            if ($subClassMetadata->hasLowerColumn($lcColumnName)) {
                return $subClassMetadata->getFieldNameForLowerColumnName($lcColumnName);
            }
        }

        \Doctrine\Common\DoctrineException::updateMe("No field name found for column name '$lcColumnName' during hydration.");
    }

    /** Needed only temporarily until the new parser is ready */
    private $_isResultMixed = false;
    public function setResultMixed($bool)
    {
        $this->_isResultMixed = $bool;
    }
}