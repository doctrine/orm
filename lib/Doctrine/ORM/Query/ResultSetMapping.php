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
 * IMPORTANT NOTE:
 * The properties of this class are only public for fast internal READ access.
 * Users should use the public methods.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ResultSetMapping
{
    /** Whether the result is mixed (contains scalar values together with field values). */
    public $isMixed = false;
    /** Maps alias names to ClassMetadata descriptors. */
    public $aliasMap = array();
    /** Maps alias names to related association mappings. */
    public $relationMap = array();
    /** Maps alias names to parent alias names. */
    public $parentAliasMap = array();
    /** Maps column names in the result set to field names for each class. */
    public $fieldMappings = array();
    /** Maps column names in the result set to the alias to use in the mapped result. */
    public $scalarMappings = array();
    /** Maps column names in the result set to the alias they belong to. */
    public $columnOwnerMap = array();
    /** List of columns in the result set that are used as discriminator columns. */
    public $discriminatorColumns = array();
    /** Maps alias names to field names that should be used for indexing. */
    public $indexByMap = array();
    /** A list of columns that should be ignored/skipped during hydration. */
    public $ignoredColumns = array();

    /**
     *
     * @param string $class The class name.
     * @param string $alias The alias for this class. The alias must be unique within this ResultSetMapping.
     */
    public function addEntityResult($class, $alias)
    {
        $this->aliasMap[$alias] = $class;
    }

    /**
     *
     * @param string $alias
     * @param string $discrColumn
     */
    public function setDiscriminatorColumn($alias, $discrColumn)
    {
        $this->discriminatorColumns[$alias] = $discrColumn;
        $this->columnOwnerMap[$discrColumn] = $alias;
    }

    /**
     *
     * @param string $className
     * @return string
     */
    public function getDiscriminatorColumn($className)
    {
        return isset($this->discriminatorColumns[$className]) ?
                $this->discriminatorColumns[$className] : null;
    }

    /**
     *
     * @param string $alias
     * @param string $fieldName
     */
    public function addIndexBy($alias, $fieldName)
    {
        $this->indexByMap[$alias] = $fieldName;
    }

    /**
     *
     * @param string $alias
     * @return boolean
     */
    public function hasIndexBy($alias)
    {
        return isset($this->indexByMap[$alias]);
    }

    /**
     *
     * @param string $alias
     * @return string
     */
    public function getIndexByField($alias)
    {
        return $this->indexByMap[$alias];
    }

    /**
     *
     * @param string $columnName
     * @return boolean
     */
    public function isFieldResult($columnName)
    {
        return isset($this->fieldMappings[$columnName]);
    }

    /**
     *
     * @param string $alias
     * @param string $columnName
     * @param string $fieldName 
     */
    public function addFieldResult($alias, $columnName, $fieldName)
    {
        $this->fieldMappings[$columnName] = $fieldName;
        $this->columnOwnerMap[$columnName] = $alias;
        if ( ! $this->isMixed && $this->scalarMappings) {
            $this->isMixed = true;
        }
    }

    /**
     *
     * @param string $class
     * @param string $alias
     * @param string $parentAlias
     * @param object $relation
     */
    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)
    {
        $this->aliasMap[$alias] = $class;
        $this->parentAliasMap[$alias] = $parentAlias;
        $this->relationMap[$alias] = $relation;
    }
    
    /**
     *
     * @param string $columnName
     * @param string $alias
     */
    public function addScalarResult($columnName, $alias)
    {
        $this->scalarMappings[$columnName] = $alias;
        if ( ! $this->isMixed && $this->fieldMappings) {
            $this->isMixed = true;
        }
    }

    /**
     * @return boolean
     */
    public function isScalarResult($columnName)
    {
        return isset($this->scalarMappings[$columnName]);
    }

    /**
     *
     * @param <type> $alias
     */
    public function getClass($alias)
    {
        return $this->aliasMap[$alias];
    }

    /**
     * Gets the alias for a column that is mapped as a scalar value.
     *
     * @param string $columnName
     * @return string
     */
    public function getScalarAlias($columnName)
    {
        return $this->scalarMappings[$columnName];
    }

    /**
     * Gets the class that owns the specified column.
     *
     * @param string $columnName
     */
    public function getOwningClass($columnName)
    {
        return $this->aliasMap[$this->columnOwnerMap[$columnName]];
    }

    /**
     *
     * @param string $alias
     * @return AssociationMapping
     */
    public function getRelation($alias)
    {
        return $this->relationMap[$alias];
    }

    /**
     *
     * @param string $alias
     * @return boolean
     */
    public function isRelation($alias)
    {
        return isset($this->relationMap[$alias]);
    }

    /**
     *
     * @param <type> $columnName
     * @return <type>
     */
    public function getEntityAlias($columnName)
    {
        return $this->columnOwnerMap[$columnName];
    }

    /**
     *
     * @param string $alias
     * @return string
     */
    public function getParentAlias($alias)
    {
        return $this->parentAliasMap[$alias];
    }

    /**
     *
     * @param string $alias
     * @return boolean
     */
    public function hasParentAlias($alias)
    {
        return isset($this->parentAliasMap[$alias]);
    }

    /**
     * Gets the field name for a column name.
     *
     * @param string $columnName
     * @return string
     */
    public function getFieldName($columnName)
    {
        return $this->fieldMappings[$columnName];
    }

    /**
     *
     * @return array
     */
    public function getAliasMap()
    {
        return $this->aliasMap;
    }

    /**
     *
     * @return integer
     */
    public function getEntityResultCount()
    {
        return count($this->aliasMap);
    }

    /**
     *
     * @return boolean
     */
    public function isMixedResult()
    {
        return $this->isMixed;
    }

    /**
     * Adds a column name that will be ignored during hydration.
     *
     * @param string $columnName
     */
    public function addIgnoredColumn($columnName)
    {
        $this->ignoredColumns[$columnName] = true;
    }

    /**
     *
     * @param string $columnName
     * @return boolean
     */
    public function isIgnoredColumn($columnName)
    {
        return isset($this->ignoredColumns[$columnName]);
    }
}

