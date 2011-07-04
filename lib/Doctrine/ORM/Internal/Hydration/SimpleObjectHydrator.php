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

use \PDO;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;

class SimpleObjectHydrator extends AbstractHydrator
{
    /**
     * @var ClassMetadata
     */
    private $class;

    private $declaringClasses = array();

    protected function _hydrateAll()
    {
        $result = array();
        $cache = array();

        while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->_hydrateRow($row, $cache, $result);
        }

        $this->_em->getUnitOfWork()->triggerEagerLoads();

        return $result;
    }

    protected function _prepare()
    {
        if (count($this->_rsm->aliasMap) == 1) {
            $this->class = $this->_em->getClassMetadata(reset($this->_rsm->aliasMap));
            if ($this->class->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
                foreach ($this->_rsm->declaringClasses AS $column => $class) {
                    $this->declaringClasses[$column] = $this->_em->getClassMetadata($class);
                }
            }
        } else {
            throw new \RuntimeException("Cannot use SimpleObjectHydrator with a ResultSetMapping not containing exactly one object result.");
        }
        if ($this->_rsm->scalarMappings) {
            throw new \RuntimeException("Cannot use SimpleObjectHydrator with a ResultSetMapping that contains scalar mappings.");
        }
    }

    protected function _hydrateRow(array $sqlResult, array &$cache, array &$result)
    {
        $data = array();
        if ($this->class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_NONE) {
            foreach ($sqlResult as $column => $value) {

                if (!isset($cache[$column])) {
                    if (isset($this->_rsm->fieldMappings[$column])) {
                        $cache[$column]['name'] = $this->_rsm->fieldMappings[$column];
                        $cache[$column]['field'] = true;
                    } else {
                        $cache[$column]['name'] = $this->_rsm->metaMappings[$column];
                    }
                }

                if (isset($cache[$column]['field'])) {
                    $value = Type::getType($this->class->fieldMappings[$cache[$column]['name']]['type'])
                                    ->convertToPHPValue($value, $this->_platform);
                }
                $data[$cache[$column]['name']] = $value;
            }
            $entityName = $this->class->name;
        } else {
            $discrColumnName = $this->_platform->getSQLResultCasing($this->class->discriminatorColumn['name']);
            $entityName = $this->class->discriminatorMap[$sqlResult[$discrColumnName]];
            unset($sqlResult[$discrColumnName]);
            foreach ($sqlResult as $column => $value) {
                if (!isset($cache[$column])) {
                    if (isset($this->_rsm->fieldMappings[$column])) {
                        $field = $this->_rsm->fieldMappings[$column];
                        $class = $this->declaringClasses[$column];
                        if ($class->name == $entityName || is_subclass_of($entityName, $class->name)) {
                            $cache[$column]['name'] = $field;
                            $cache[$column]['class'] = $class;
                        }
                    } else if (isset($this->_rsm->relationMap[$column])) {
                        if ($this->_rsm->relationMap[$column] == $entityName || is_subclass_of($entityName, $this->_rsm->relationMap[$column])) {
                            $cache[$column]['name'] = $field;
                        }
                    } else {
                        $cache[$column]['name'] = $this->_rsm->metaMappings[$column];
                    }
                }

                if (isset($cache[$column]['class'])) {
                    $value = Type::getType($cache[$column]['class']->fieldMappings[$cache[$column]['name']]['type'])
                                    ->convertToPHPValue($value, $this->_platform);
                }

                // the second and part is to prevent overwrites in case of multiple
                // inheritance classes using the same property name (See AbstractHydrator)
                if (isset($cache[$column]) && (!isset($data[$cache[$column]['name']]) || $value !== null)) {
                    $data[$cache[$column]['name']] = $value;
                }
            }
        }

        if (isset($this->_hints[Query::HINT_REFRESH_ENTITY])) {
            $this->registerManaged($this->class, $this->_hints[Query::HINT_REFRESH_ENTITY], $data);
        }

        $result[] = $this->_em->getUnitOfWork()->createEntity($entityName, $data, $this->_hints);
    }
}