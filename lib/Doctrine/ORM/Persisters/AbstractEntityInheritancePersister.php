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

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\DBAL\Types\Type;

/**
 * Base class for entity persisters that implement a certain inheritance mapping strategy.
 * All these persisters are assumed to use a discriminator column to discriminate entity
 * types in the hierarchy.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 */
abstract class AbstractEntityInheritancePersister extends BasicEntityPersister
{
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
    protected function _getSelectColumnSQL($field, ClassMetadata $class, $alias = 'r')
    {
        $columnName = $class->columnNames[$field];
        $sql = $this->_getSQLTableAlias($class->name, $alias == 'r' ? '' : $alias) . '.' . $this->quoteStrategy->getColumnName($field, $class, $this->_platform);
        $columnAlias = $this->getSQLColumnAlias($columnName);
        $this->_rsm->addFieldResult($alias, $columnAlias, $field, $class->name);

        if (isset($class->fieldMappings[$field]['requireSQLConversion'])) {
            $type = Type::getType($class->getTypeOfField($field));
            $sql = $type->convertToPHPValueSQL($sql, $this->_platform);
        }

        return $sql . ' AS ' . $columnAlias;
    }

    protected function getSelectJoinColumnSQL($tableAlias, $joinColumnName, $className)
    {
        $columnAlias = $this->getSQLColumnAlias($joinColumnName);
        $this->_rsm->addMetaResult('r', $columnAlias, $joinColumnName);

        return $tableAlias . '.' . $joinColumnName . ' AS ' . $columnAlias;
    }
}
