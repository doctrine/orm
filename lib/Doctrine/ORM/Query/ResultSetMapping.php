<?php
/*
 *  $Id$
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

namespace Doctrine\ORM\Query;

/**
 * A ResultSetMapping describes how a result set of an SQL query maps to a Doctrine result.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ResultSetMapping
{
    /** Whether the result is mixed (contains scalar values together with field values). */
    private $_isMixed = false;
    /** Maps alias names to ClassMetadata descriptors. */
    private $_aliasMap = array();
    /** Maps alias names to related association mappings. */
    private $_relationMap = array();
    /** Maps alias names to parent alias names. */
    private $_parentAliasMap = array();
    /** Maps column names in the result set to field names for each class. */
    private $_fieldMappings = array();
    /** Maps column names in the result set to the alias to use in the mapped result. */
    private $_scalarMappings = array();
    /** Maps column names in the result set to the alias they belong to. */
    private $_columnOwnerMap = array();
    /** List of columns in the result set that are used as discriminator columns. */
    private $_discriminatorColumns = array();
    /** Maps alias names to field names that should be used for indexing. */
    private $_indexByMap = array();
    /** A list of columns that should be ignored/skipped during hydration. */
    private $_ignoredColumns = array();

    /**
     *
     * @param <type> $class
     * @param <type> $alias The alias for this class. The alias must be unique within this ResultSetMapping.
     * @param <type> $discriminatorColumn
     */
    public function addEntityResult($class, $alias)
    {
        $this->_aliasMap[$alias] = $class;
    }

    public function setDiscriminatorColumn($className, $alias, $discrColumn)
    {
        $this->_discriminatorColumns[$className] = $discrColumn;
        $this->_columnOwnerMap[$discrColumn] = $alias;
    }

    public function getDiscriminatorColumn($className)
    {
        return isset($this->_discriminatorColumns[$className]) ?
                $this->_discriminatorColumns[$className] : null;
    }

    public function addIndexBy($alias, $fieldName)
    {
        $this->_indexByMap[$alias] = $fieldName;
    }

    public function hasIndexBy($alias)
    {
        return isset($this->_indexByMap[$alias]);
    }

    public function getIndexByField($alias)
    {
        return $this->_indexByMap[$alias];
    }

    public function isFieldResult($columnName)
    {
        return isset($this->_fieldMappings[$columnName]);
    }

    public function addFieldResult($alias, $columnName, $fieldName)
    {
        $this->_fieldMappings[$columnName] = $fieldName;
        $this->_columnOwnerMap[$columnName] = $alias;
        if ( ! $this->_isMixed && $this->_scalarMappings) {
            $this->_isMixed = true;
        }
    }

    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)
    {
        $this->_aliasMap[$alias] = $class;
        $this->_parentAliasMap[$alias] = $parentAlias;
        $this->_relationMap[$alias] = $relation;
    }

    /*public function isDiscriminatorColumn($columnName)
    {
        return isset($this->_discriminatorMap[$columnName]);
    }*/

    public function addScalarResult($columnName, $alias)
    {
        $this->_scalarMappings[$columnName] = $alias;
        if ( ! $this->_isMixed && $this->_fieldMappings) {
            $this->_isMixed = true;
        }
    }    

    /**
     * @return boolean
     */
    public function isScalarResult($columnName)
    {
        return isset($this->_scalarMappings[$columnName]);
    }

    /**
     *
     * @param <type> $alias
     */
    public function getClass($alias)
    {
        return $this->_aliasMap[$alias];
    }

    /**
     * Gets the alias for a column that is mapped as a scalar value.
     *
     * @param string $columnName
     * @return string
     */
    public function getScalarAlias($columnName)
    {
        return $this->_scalarMappings[$columnName];
    }

    /**
     * Gets the class that owns the specified column.
     *
     * @param string $columnName
     */
    public function getOwningClass($columnName)
    {
        return $this->_aliasMap[$this->_columnOwnerMap[$columnName]];
    }

    public function getRelation($alias)
    {
        return $this->_relationMap[$alias];
    }

    /**
     *
     * @param <type> $columnName
     * @return <type>
     */
    public function getEntityAlias($columnName)
    {
        return $this->_columnOwnerMap[$columnName];
    }

    /**
     *
     * @param <type> $alias
     * @return <type> 
     */
    public function getParentAlias($alias)
    {
        return $this->_parentAliasMap[$alias];
    }

    public function hasParentAlias($alias)
    {
        return isset($this->_parentAliasMap[$alias]);
    }

    /**
     *
     * @param <type> $className
     * @param <type> $columnName
     * @return <type> 
     */
    public function getFieldName($columnName)
    {
        return $this->_fieldMappings[$columnName];
    }

    public function getAliasMap()
    {
        return $this->_aliasMap;
    }

    public function getEntityResultCount()
    {
        return count($this->_aliasMap);
    }

    public function isMixedResult()
    {
        return $this->_isMixed;
    }

    public function addIgnoredColumn($columnName)
    {
        $this->_ignoredColumns[$columnName] = true;
    }

    public function isIgnoredColumn($columnName)
    {
        return isset($this->_ignoredColumns[$columnName]);
    }
}

