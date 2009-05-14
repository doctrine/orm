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
     * @param <type> $class
     * @param <type> $alias The alias for this class. The alias must be unique within this ResultSetMapping.
     * @param <type> $discriminatorColumn
     */
    public function addEntityResult($class, $alias)
    {
        $this->aliasMap[$alias] = $class;
    }

    public function setDiscriminatorColumn($className, $alias, $discrColumn)
    {
        $this->discriminatorColumns[$className] = $discrColumn;
        $this->columnOwnerMap[$discrColumn] = $alias;
    }

    public function getDiscriminatorColumn($className)
    {
        return isset($this->discriminatorColumns[$className]) ?
                $this->discriminatorColumns[$className] : null;
    }

    public function addIndexBy($alias, $fieldName)
    {
        $this->indexByMap[$alias] = $fieldName;
    }

    public function hasIndexBy($alias)
    {
        return isset($this->indexByMap[$alias]);
    }

    public function getIndexByField($alias)
    {
        return $this->indexByMap[$alias];
    }

    public function isFieldResult($columnName)
    {
        return isset($this->fieldMappings[$columnName]);
    }

    public function addFieldResult($alias, $columnName, $fieldName)
    {
        $this->fieldMappings[$columnName] = $fieldName;
        $this->columnOwnerMap[$columnName] = $alias;
        if ( ! $this->isMixed && $this->scalarMappings) {
            $this->isMixed = true;
        }
    }

    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)
    {
        $this->aliasMap[$alias] = $class;
        $this->parentAliasMap[$alias] = $parentAlias;
        $this->relationMap[$alias] = $relation;
    }

    /*public function isDiscriminatorColumn($columnName)
    {
        return isset($this->_discriminatorMap[$columnName]);
    }*/

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

    public function getRelation($alias)
    {
        return $this->relationMap[$alias];
    }

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
     * @param <type> $alias
     * @return <type> 
     */
    public function getParentAlias($alias)
    {
        return $this->parentAliasMap[$alias];
    }

    public function hasParentAlias($alias)
    {
        return isset($this->parentAliasMap[$alias]);
    }

    /**
     *
     * @param <type> $className
     * @param <type> $columnName
     * @return <type> 
     */
    public function getFieldName($columnName)
    {
        return $this->fieldMappings[$columnName];
    }

    public function getAliasMap()
    {
        return $this->aliasMap;
    }

    public function getEntityResultCount()
    {
        return count($this->aliasMap);
    }

    public function isMixedResult()
    {
        return $this->isMixed;
    }

    public function addIgnoredColumn($columnName)
    {
        $this->ignoredColumns[$columnName] = true;
    }

    public function isIgnoredColumn($columnName)
    {
        return isset($this->ignoredColumns[$columnName]);
    }
}

