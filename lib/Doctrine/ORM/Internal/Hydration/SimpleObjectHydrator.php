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

use \PDO,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Events,
    Doctrine\ORM\Query;

class SimpleObjectHydrator extends AbstractHydrator
{
    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @var array
     */
    private $declaringClasses = array();

    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = array();
        $cache = array();

        while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->hydrateRowData($row, $cache, $result);
        }

        $this->_em->getUnitOfWork()->triggerEagerLoads();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepare()
    {
        if (count($this->_rsm->aliasMap) !== 1) {
            throw new \RuntimeException("Cannot use SimpleObjectHydrator with a ResultSetMapping that contains more than one object result.");
        }

        if ($this->_rsm->scalarMappings) {
            throw new \RuntimeException("Cannot use SimpleObjectHydrator with a ResultSetMapping that contains scalar mappings.");
        }

        $this->class = $this->_em->getClassMetadata(reset($this->_rsm->aliasMap));

        // We only need to add declaring classes if we have inheritance.
        if ($this->class->inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
            return;
        }

        foreach ($this->_rsm->declaringClasses as $column => $class) {
            $this->declaringClasses[$column] = $this->_em->getClassMetadata($class);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $sqlResult, array &$cache, array &$result)
    {
        $entityName = $this->class->name;
        $data       = array();

        // We need to find the correct entity class name if we have inheritance in resultset
        if ($this->class->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            $discrColumnName = $this->_platform->getSQLResultCasing($this->class->discriminatorColumn['name']);

            if ( ! isset($sqlResult[$discrColumnName])) {
                throw HydrationException::missingDiscriminatorColumn($entityName, $discrColumnName, key($this->_rsm->aliasMap));
            }

            if ($sqlResult[$discrColumnName] === '') {
                throw HydrationException::emptyDiscriminatorValue(key($this->_rsm->aliasMap));
            }

            $entityName = $this->class->discriminatorMap[$sqlResult[$discrColumnName]];

            unset($sqlResult[$discrColumnName]);
        }

        foreach ($sqlResult as $column => $value) {
            // Hydrate column information if not yet present
            if ( ! isset($cache[$column])) {
                if (($info = $this->hydrateColumnInfo($entityName, $column)) === null) {
                    continue;
                }

                $cache[$column] = $info;
            }

            // Convert field to a valid PHP value
            if (isset($cache[$column]['field'])) {
                $type  = Type::getType($cache[$column]['class']->fieldMappings[$cache[$column]['name']]['type']);
                $value = $type->convertToPHPValue($value, $this->_platform);
            }

            // Prevent overwrite in case of inherit classes using same property name (See AbstractHydrator)
            if (isset($cache[$column]) && ( ! isset($data[$cache[$column]['name']]) || $value !== null)) {
                $data[$cache[$column]['name']] = $value;
            }
        }

        if (isset($this->_hints[Query::HINT_REFRESH_ENTITY])) {
            $this->registerManaged($this->class, $this->_hints[Query::HINT_REFRESH_ENTITY], $data);
        }

        $uow    = $this->_em->getUnitOfWork();
        $entity = $uow->createEntity($entityName, $data, $this->_hints);

        $result[] = $entity;
    }

    /**
     * Retrieve column information form ResultSetMapping.
     *
     * @param string $entityName
     * @param string $column
     *
     * @return array
     */
    protected function hydrateColumnInfo($entityName, $column)
    {
        switch (true) {
            case (isset($this->_rsm->fieldMappings[$column])):
                $class = isset($this->declaringClasses[$column])
                    ? $this->declaringClasses[$column]
                    : $this->class;

                // If class is not part of the inheritance, ignore
                if ( ! ($class->name === $entityName || is_subclass_of($entityName, $class->name))) {
                    return null;
                }

                return array(
                    'class' => $class,
                    'name'  => $this->_rsm->fieldMappings[$column],
                    'field' => true,
                );

            case (isset($this->_rsm->relationMap[$column])):
                $class = isset($this->_rsm->relationMap[$column])
                    ? $this->_rsm->relationMap[$column]
                    : $this->class;

                // If class is not self referencing, ignore
                if ( ! ($class === $entityName || is_subclass_of($entityName, $class))) {
                    return null;
                }

                // TODO: Decide what to do with associations. It seems original code is incomplete.
                // One solution is to load the association, but it might require extra efforts.
                return array('name' => $column);

            default:
                return array(
                    'name' => $this->_rsm->metaMappings[$column]
                );
        }
    }
}
