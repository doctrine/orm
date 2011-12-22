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

namespace Doctrine\ORM;

/**
 * The default NamingStrategy
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultNamingStrategy implements NamingStrategy
{

    /**
     * {@inheritdoc}
     */
    public function classToTableName($className)
    {
        return $className;
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName($propertyName)
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function tableName($tableName)
    {
        return $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function columnName($columnName)
    {
        return $columnName;
    }

    /**
     * {@inheritdoc}
     */
    public function collectionTableName($ownerEntity, $ownerEntityTable, $associatedEntity, $associatedEntityTable, $propertyName)
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName($joinedColumn, $joinedTable)
    {
        return $joinedColumn;
    }

    /**
     * {@inheritdoc}
     */
    public function foreignKeyColumnName($propertyName, $propertyEntityName, $propertyTableName, $referencedColumnName)
    {
        return $propertyName ?: $propertyTableName;
    }

    /**
     * {@inheritdoc}
     */
    public function logicalColumnName($columnName, $propertyName)
    {
        return $columnName ?: $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function logicalCollectionTableName($tableName, $ownerEntityTable, $associatedEntityTable, $propertyName)
    {
        return $ownerEntityTable . '_' . ( $associatedEntityTable ?: $propertyName );
    }

    /**
     * {@inheritdoc}
     */
    public function logicalCollectionColumnName($columnName, $propertyName, $referencedColumn)
    {
        return $columnName ?: ($propertyName . '_' . $referencedColumn);
    }
}
