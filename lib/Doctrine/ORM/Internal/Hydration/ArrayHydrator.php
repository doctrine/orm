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
// use Doctrine\DBAL\Connection; /* unused use */
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The ArrayHydrator produces a nested array "graph" that is often (not always)
 * interchangeable with the corresponding object graph for read-only access.
 *
 * @since  2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Guilherme Blanco <guilhermeblanoc@hotmail.com>
 */
class ArrayHydrator extends AbstractHydrator
{
    private $ce = array();
    private $rootAliases = array();
    private $isSimpleQuery = false;
    private $identifierMap = array();
    private $resultPointers = array();
    private $idTemplate = array();
    private $resultCounter = 0;

    /**
     * {@inheritdoc}
     */
    protected function prepare()
    {
        $this->isSimpleQuery  = count($this->rsm->aliasMap) <= 1;
        $this->identifierMap  = array();
        $this->resultPointers = array();
        $this->idTemplate     = array();
        $this->resultCounter  = 0;

        foreach ($this->rsm->aliasMap as $dqlAlias => $className) {
            $this->identifierMap[$dqlAlias]  = array();
            $this->resultPointers[$dqlAlias] = array();
            $this->idTemplate[$dqlAlias]     = '';
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = array();
        $cache  = array();

        while ($data = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->hydrateRowData($data, $cache, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $row, array &$cache, array &$result)
    {
        // 1) Initialize
        $id = $this->idTemplate; // initialize the id-memory
        $nonemptyComponents = array();
        $rowData = $this->gatherRowData($row, $cache, $id, $nonemptyComponents);

        // Extract scalar values. They're appended at the end.
        if (isset($rowData['scalars'])) {
            $scalars = $rowData['scalars'];

            unset($rowData['scalars']);

            if (empty($rowData)) {
                ++$this->resultCounter;
            }
        }

        // 2) Now hydrate the data found in the current row.
        foreach ($rowData as $dqlAlias => $data) {
            $index = false;

            if (isset($this->rsm->parentAliasMap[$dqlAlias])) {
                // It's a joined result

                $parent = $this->rsm->parentAliasMap[$dqlAlias];
                $path   = $parent . '.' . $dqlAlias;

                // missing parent data, skipping as RIGHT JOIN hydration is not supported.
                if ( ! isset($nonemptyComponents[$parent]) ) {
                    continue;
                }

                // Get a reference to the right element in the result tree.
                // This element will get the associated element attached.
                if ($this->rsm->isMixed && isset($this->rootAliases[$parent])) {
                    $first = reset($this->resultPointers);
                    // TODO: Exception if $key === null ?
                    $baseElement =& $this->resultPointers[$parent][key($first)];
                } elseif (isset($this->resultPointers[$parent])) {
                    $baseElement =& $this->resultPointers[$parent];
                } else {
                    unset($this->resultPointers[$dqlAlias]); // Ticket #1228
                    continue;
                }

                $relationAlias = $this->rsm->relationMap[$dqlAlias];
                $relation = $this->getClassMetadata($this->rsm->aliasMap[$parent])->associationMappings[$relationAlias];

                // Check the type of the relation (many or single-valued)
                if ( ! ($relation['type'] & ClassMetadata::TO_ONE)) {
                    $oneToOne = false;

                    if (isset($nonemptyComponents[$dqlAlias])) {
                        if ( ! isset($baseElement[$relationAlias])) {
                            $baseElement[$relationAlias] = array();
                        }

                        $indexExists  = isset($this->identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        if ($indexExists) {
                            $index = $this->identifierMap[$path][$id[$parent]][$id[$dqlAlias]];
                        } else {
                            $index = false;
                        }
                        $indexIsValid = $index !== false ? isset($baseElement[$relationAlias][$index]) : false;

                        if ( ! $indexExists || ! $indexIsValid) {
                            $element = $data;
                            if (isset($this->rsm->indexByMap[$dqlAlias])) {
                                $baseElement[$relationAlias][$row[$this->rsm->indexByMap[$dqlAlias]]] = $element;
                            } else {
                                $baseElement[$relationAlias][] = $element;
                            }

                            end($baseElement[$relationAlias]);

                            $key = key($baseElement[$relationAlias]);
                            $this->identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $key;
                        }
                    } elseif ( ! isset($baseElement[$relationAlias])) {
                        $baseElement[$relationAlias] = array();
                    }
                } else {
                    $oneToOne = true;

                    if ( ! isset($nonemptyComponents[$dqlAlias]) && ! isset($baseElement[$relationAlias])) {
                        $baseElement[$relationAlias] = null;
                    } elseif ( ! isset($baseElement[$relationAlias])) {
                        $baseElement[$relationAlias] = $data;
                    }
                }

                $coll =& $baseElement[$relationAlias];

                if ($coll !== null) {
                    $this->updateResultPointer($coll, $index, $dqlAlias, $oneToOne);
                }

            } else {
                // It's a root result element

                $this->rootAliases[$dqlAlias] = true; // Mark as root
                $entityKey = $this->rsm->entityMappings[$dqlAlias] ?: 0;

                // if this row has a NULL value for the root result id then make it a null result.
                if ( ! isset($nonemptyComponents[$dqlAlias]) ) {
                    if ($this->rsm->isMixed) {
                        $result[] = array($entityKey => null);
                    } else {
                        $result[] = null;
                    }
                    $resultKey = $this->resultCounter;
                    ++$this->resultCounter;
                    continue;
                }

                // Check for an existing element
                if ($this->isSimpleQuery || ! isset($this->identifierMap[$dqlAlias][$id[$dqlAlias]])) {
                    $element = $rowData[$dqlAlias];
                    if ($this->rsm->isMixed) {
                        $element = array($entityKey => $element);
                    }

                    if (isset($this->rsm->indexByMap[$dqlAlias])) {
                        $resultKey = $row[$this->rsm->indexByMap[$dqlAlias]];
                        $result[$resultKey] = $element;
                    } else {
                        $resultKey = $this->resultCounter;
                        $result[] = $element;
                        ++$this->resultCounter;
                    }

                    $this->identifierMap[$dqlAlias][$id[$dqlAlias]] = $resultKey;
                } else {
                    $index = $this->identifierMap[$dqlAlias][$id[$dqlAlias]];
                    $resultKey = $index;
                    /*if ($this->_rsm->isMixed) {
                        $result[] =& $result[$index];
                        ++$this->_resultCounter;
                    }*/
                }
                $this->updateResultPointer($result, $index, $dqlAlias, false);
            }
        }

        // Append scalar values to mixed result sets
        if (isset($scalars)) {
            if ( ! isset($resultKey) ) {
                // this only ever happens when no object is fetched (scalar result only)
                if (isset($this->rsm->indexByMap['scalars'])) {
                    $resultKey = $row[$this->rsm->indexByMap['scalars']];
                } else {
                    $resultKey = $this->resultCounter - 1;
                }
            }

            foreach ($scalars as $name => $value) {
                $result[$resultKey][$name] = $value;
            }
        }
    }

    /**
     * Updates the result pointer for an Entity. The result pointers point to the
     * last seen instance of each Entity type. This is used for graph construction.
     *
     * @param array $coll  The element.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     * @param boolean $oneToOne  Whether it is a single-valued association or not.
     */
    private function updateResultPointer(array &$coll, $index, $dqlAlias, $oneToOne)
    {
        if ($coll === null) {
            unset($this->resultPointers[$dqlAlias]); // Ticket #1228

            return;
        }

        if ($index !== false) {
            $this->resultPointers[$dqlAlias] =& $coll[$index];

            return;
        }

        if ( ! $coll) {
            return;
        }

        if ($oneToOne) {
            $this->resultPointers[$dqlAlias] =& $coll;

            return;
        }

        end($coll);
        $this->resultPointers[$dqlAlias] =& $coll[key($coll)];

        return;
    }

    /**
     * Retrieve ClassMetadata associated to entity class name.
     *
     * @param string $className
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getClassMetadata($className)
    {
        if ( ! isset($this->ce[$className])) {
            $this->ce[$className] = $this->em->getClassMetadata($className);
        }

        return $this->ce[$className];
    }
}
