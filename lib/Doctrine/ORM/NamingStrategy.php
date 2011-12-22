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
 * A set of rules for determining the physical column and table names
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.3
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface NamingStrategy
{

    /**
     * Return a table name for an entity class
     *
     * @param   string  $className  The fully-qualified class name
     * @return  string              A table name
     */
    function classToTableName($className);

    /**
     * Return a column name for a property path expression
     *
     * @param   string  $propertyName   A property path
     * @return  string                  A column name
     */
    function propertyToColumnName($propertyName);

    /**
     * Alter the table name given in the mapping document
     *
     * @param   string  tableName   A table name
     * @return  string              A table name
     */
    function tableName($tableName);

    /**
     * Alter the column name given in the mapping document
     * 
     * @param   string $columnName  A column name
     * @return  string              A column name
     */
    function columnName($columnName);

    /**
     * Return a collection table name ie an association having a join table
     *
     * @param string   $ownerEntity
     * @param string   $ownerEntityTable        Owner side table name
     * @param string   $associatedEntity
     * @param string   $associatedEntityTable   Reverse side table name if any
     * @param string   $propertyName            Collection role
     */
    function collectionTableName($ownerEntity, $ownerEntityTable, $associatedEntity, $associatedEntityTable, $propertyName);

    /**
     * Return the join key column name ie a FK column used in a JOINED strategy or for a secondary table
     *
     * @param string   $joinedColumn    Joined column name used to join with
     * @param string   $joinedTable     Joined table name used to join with
     */
    function joinKeyColumnName($joinedColumn, $joinedTable);

    /**
     * Return the foreign key column name for the given parameters
     *
     * @param string   $propertyName            The property name involved
     * @param string   $propertyEntityName
     * @param string   $propertyTableName       The property table name involved (logical one)
     * @param string   $referencedColumnName    The referenced column name involved (logical one)
     */
    function foreignKeyColumnName($propertyName, $propertyEntityName, $propertyTableName, $referencedColumnName);

    /**
     * Return the logical column name used to refer to a column in the metadata
     *
     * @param string   $columnName      Given column name if any
     * @param string   $propertyName    Property name of this column
     */
    function logicalColumnName($columnName, $propertyName);

    /**
     * Returns the logical collection table name used to refer to a table in the mapping metadata
     *
     * @param string   $tableName               The metadata explicit name
     * @param string   $ownerEntityTable        Owner table entity table name (logical one)
     * @param string   $associatedEntityTable   Reverse side table name if any (logical one)
     * @param string   $propertyName            Collection role
     */
    function logicalCollectionTableName($tableName, $ownerEntityTable, $associatedEntityTable, $propertyName);

    /**
     * Returns the logical foreign key column name used to refer to this column in the mapping metadata
     *
     * @param string   $columnName          Given column name in the metadata if any
     * @param string   $propertyName        Property name
     * @param string   $referencedColumn    Referenced column name in the join
     */
    function logicalCollectionColumnName($columnName, $propertyName, $referencedColumn);
}
