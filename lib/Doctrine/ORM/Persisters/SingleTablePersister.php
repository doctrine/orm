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

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Persister for entities that participate in a hierarchy mapped with the
 * SINGLE_TABLE strategy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 * @link http://martinfowler.com/eaaCatalog/singleTableInheritance.html
 */
class SingleTablePersister extends AbstractEntityInheritancePersister
{
    /** {@inheritdoc} */
    protected function _getDiscriminatorColumnTableName()
    {
        return $this->_class->table['name'];
    }

    /** {@inheritdoc} */
    protected function _getSelectColumnListSQL()
    {
        if ($this->_selectColumnListSql !== null) {
            return $this->_selectColumnListSql;
        }

        $columnList = parent::_getSelectColumnListSQL();

        // Append discriminator column
        $discrColumn = $this->_class->discriminatorColumn['name'];
        $columnList .= ", $discrColumn";
        $rootClass = $this->_em->getClassMetadata($this->_class->rootEntityName);
        $tableAlias = $this->_getSQLTableAlias($rootClass->name);
        $resultColumnName = $this->_platform->getSQLResultCasing($discrColumn);
        $this->_rsm->setDiscriminatorColumn('r', $resultColumnName);
        $this->_rsm->addMetaResult('r', $resultColumnName, $discrColumn);

        // Append subclass columns
        foreach ($this->_class->subClasses as $subClassName) {
            $subClass = $this->_em->getClassMetadata($subClassName);
            // Regular columns
            foreach ($subClass->fieldMappings as $fieldName => $mapping) {
                if ( ! isset($mapping['inherited'])) {
                    $columnList .= ', ' . $this->_getSelectColumnSQL($fieldName, $subClass);
                }
            }
            // Foreign key columns
            foreach ($subClass->associationMappings as $assoc) {
                if ($assoc['isOwningSide'] && $assoc['type'] & ClassMetadata::TO_ONE && ! isset($assoc['inherited'])) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $srcColumn) {
                        if ($columnList != '') $columnList .= ', ';
                        $columnList .= $this->getSelectJoinColumnSQL($tableAlias, $srcColumn,
                            isset($assoc['inherited']) ? $assoc['inherited'] : $this->_class->name
                        );
                    }
                }
            }
        }

        $this->_selectColumnListSql = $columnList;
        return $this->_selectColumnListSql;
    }

    /** {@inheritdoc} */
    protected function _getInsertColumnList()
    {
        $columns = parent::_getInsertColumnList();
        // Add discriminator column to the INSERT SQL
        $columns[] = $this->_class->discriminatorColumn['name'];

        return $columns;
    }

    /** {@inheritdoc} */
    protected function _getSQLTableAlias($className, $assocName = '')
    {
        return parent::_getSQLTableAlias($this->_class->rootEntityName, $assocName);
    }

    /** {@inheritdoc} */
    protected function _getSelectConditionSQL(array $criteria, $assoc = null)
    {
        $conditionSql = parent::_getSelectConditionSQL($criteria, $assoc);

        // Append discriminator condition
        if ($conditionSql) $conditionSql .= ' AND ';
        $values = array();
        if ($this->_class->discriminatorValue !== null) { // discriminators can be 0
            $values[] = $this->_conn->quote($this->_class->discriminatorValue);
        }

        $discrValues = array_flip($this->_class->discriminatorMap);
        foreach ($this->_class->subClasses as $subclassName) {
            $values[] = $this->_conn->quote($discrValues[$subclassName]);
        }
        $conditionSql .= $this->_getSQLTableAlias($this->_class->name) . '.'
                . $this->_class->discriminatorColumn['name']
                . ' IN (' . implode(', ', $values) . ')';

        return $conditionSql;
    }
}