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

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\DBAL\Types\Type;

/**
 * Base class for entity persisters that implement a certain inheritance mapping strategy.
 * All these persisters are assumed to use a discriminator column to discriminate entity
 * types in the hierarchy.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
abstract class AbstractEntityInheritancePersister extends BasicEntityPersister
{
    /**
     * Map from column names to class metadata instances that declare the field the column is mapped to.
     * 
     * @var array
     */
    private $declaringClassMap = array();

    /**
     * Map from column names to class names that declare the field the association with join column is mapped to.
     *
     * @var array
     */
    private $declaringJoinColumnMap = array();

    /**
     * {@inheritdoc}
     */
    protected function _prepareInsertData($entity)
    {
        $data = parent::_prepareInsertData($entity);
        // Populate the discriminator column
        $discColumn = $this->_class->discriminatorColumn;
        $this->_columnTypes[$discColumn['name']] = $discColumn['type'];
        $data[$this->_getDiscriminatorColumnTableName()][$discColumn['name']] = $this->_class->discriminatorValue;
        return $data;
    }

    /**
     * Gets the name of the table that contains the discriminator column.
     * 
     * @return string The table name.
     */
    abstract protected function _getDiscriminatorColumnTableName();

    /**
     * {@inheritdoc}
     */
    protected function _processSQLResult(array $sqlResult)
    {
        $data = array();
        $discrColumnName = $this->_platform->getSQLResultCasing($this->_class->discriminatorColumn['name']);
        $entityName = $this->_class->discriminatorMap[$sqlResult[$discrColumnName]];
        unset($sqlResult[$discrColumnName]);
        foreach ($sqlResult as $column => $value) {
            $realColumnName = $this->_resultColumnNames[$column];
            if (isset($this->declaringClassMap[$column])) {
                $class = $this->declaringClassMap[$column];
                if ($class->name == $entityName || is_subclass_of($entityName, $class->name)) {
                    $field = $class->fieldNames[$realColumnName];
                    if (isset($data[$field])) {
                        $data[$realColumnName] = $value;
                    } else {
                        $data[$field] = Type::getType($class->fieldMappings[$field]['type'])
                                ->convertToPHPValue($value, $this->_platform);
                    }
                }
            } else if (isset($this->declaringJoinColumnMap[$column])) {
                if ($this->declaringJoinColumnMap[$column] == $entityName || is_subclass_of($entityName, $this->declaringJoinColumnMap[$column])) {
                    $data[$realColumnName] = $value;
                }
            } else {
                $data[$realColumnName] = $value;
            }
        }

        return array($entityName, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getSelectColumnSQL($field, ClassMetadata $class)
    {
        $columnName = $class->columnNames[$field];
        $sql = $this->_getSQLTableAlias($class->name) . '.' . $class->getQuotedColumnName($field, $this->_platform);
        $columnAlias = $this->_platform->getSQLResultCasing($columnName . $this->_sqlAliasCounter++);
        if ( ! isset($this->_resultColumnNames[$columnAlias])) {
            $this->_resultColumnNames[$columnAlias] = $columnName;
            $this->declaringClassMap[$columnAlias] = $class;
        }

        return "$sql AS $columnAlias";
    }

    protected function getSelectJoinColumnSQL($tableAlias, $joinColumnName, $className)
    {
        $columnAlias = $joinColumnName . $this->_sqlAliasCounter++;
        $resultColumnName = $this->_platform->getSQLResultCasing($columnAlias);
        if ( ! isset($this->_resultColumnNames[$resultColumnName])) {
            $this->_resultColumnNames[$resultColumnName] = $joinColumnName;
            $this->declaringJoinColumnMap[$resultColumnName] = $className;
        }
        
        return $tableAlias . ".$joinColumnName AS $columnAlias";
    }
}