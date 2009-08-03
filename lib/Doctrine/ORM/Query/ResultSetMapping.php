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
 * The properties of this class are only public for fast internal READ access and to (drastically)
 * reduce the size of serialized instances for more effective caching due to better (un-)serialization
 * performance.
 * 
 * Users should use the public methods.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @todo Do not store AssociationMappings in $relationMap. These bloat serialized instances
 *       and in turn unserialize performance suffers which is important for most effective caching.
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
    /** Maps column names of meta columns (foreign keys, discriminator columns, ...) to field names. */
    public $metaMappings = array();
    /** Maps column names in the result set to the alias they belong to. */
    public $columnOwnerMap = array();
    /** List of columns in the result set that are used as discriminator columns. */
    public $discriminatorColumns = array();
    /** Maps alias names to field names that should be used for indexing. */
    public $indexByMap = array();
    /** A list of columns that should be ignored/skipped during hydration. */
    //public $ignoredColumns = array();

    /**
     * Adds an entity result to this ResultSetMapping.
     *
     * @param string $class The class name of the entity.
     * @param string $alias The alias for the class. The alias must be unique among all entity
     *                      results or joined entity results within this ResultSetMapping.
     */
    public function addEntityResult($class, $alias)
    {
        $this->aliasMap[$alias] = $class;
    }

    /**
     * Sets a discriminator column for an entity result or joined entity result.
     * The discriminator column will be used to determine the concrete class name to
     * instantiate.
     *
     * @param string $alias The alias of the entity result or joined entity result the discriminator
     *                      column should be used for.
     * @param string $discrColumn The name of the discriminator column in the SQL result set.
     */
    public function setDiscriminatorColumn($alias, $discrColumn)
    {
        $this->discriminatorColumns[$alias] = $discrColumn;
        $this->columnOwnerMap[$discrColumn] = $alias;
    }

    /**
     * Sets a field to use for indexing an entity result or joined entity result.
     *
     * @param string $alias The alias of an entity result or joined entity result.
     * @param string $fieldName The name of the field to use for indexing.
     */
    public function addIndexBy($alias, $fieldName)
    {
        $this->indexByMap[$alias] = $fieldName;
    }

    /**
     * Checks whether an entity result or joined entity result with a given alias has
     * a field set for indexing.
     *
     * @param string $alias
     * @return boolean
     */
    public function hasIndexBy($alias)
    {
        return isset($this->indexByMap[$alias]);
    }

    /**
     * Checks whether the column with the given name is mapped as a field result
     * as part of an entity result or joined entity result.
     *
     * @param string $columnName The name of the column in the SQL result set.
     * @return boolean
     */
    public function isFieldResult($columnName)
    {
        return isset($this->fieldMappings[$columnName]);
    }

    /**
     * Adds a field result that is part of an entity result or joined entity result.
     *
     * @param string $alias The alias of the entity result or joined entity result.
     * @param string $columnName The name of the column in the SQL result set.
     * @param string $fieldName The name of the field on the (joined) entity.
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
     * Adds a joined entity result.
     *
     * @param string $class The class name of the joined entity.
     * @param string $alias The unique alias to use for the joined entity.
     * @param string $parentAlias The alias of the entity result that is the parent of this joined result.
     * @param object $relation The association that connects the parent entity result with the joined entity result.
     */
    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)
    {
        $this->aliasMap[$alias] = $class;
        $this->parentAliasMap[$alias] = $parentAlias;
        $this->relationMap[$alias] = $relation;
    }
    
    /**
     * Adds a scalar result mapping.
     *
     * @param string $columnName The name of the column in the SQL result set.
     * @param string $alias The field alias with which the scalar result should be placed in the result structure.
     */
    public function addScalarResult($columnName, $alias)
    {
        $this->scalarMappings[$columnName] = $alias;
        if ( ! $this->isMixed && $this->fieldMappings) {
            $this->isMixed = true;
        }
    }

    /**
     * Checks whether a column with a given name is mapped as a scalar result.
     * 
     * @param string $columName The name of the column in the SQL result set.
     * @return boolean
     */
    public function isScalarResult($columnName)
    {
        return isset($this->scalarMappings[$columnName]);
    }

    /**
     * Gets the name of the class of an entity result or joined entity result,
     * identified by the given unique alias.
     *
     * @param string $alias
     * @return string
     */
    public function getClassName($alias)
    {
        return $this->aliasMap[$alias];
    }

    /**
     * Gets the field alias for a column that is mapped as a scalar value.
     *
     * @param string $columnName The name of the column in the SQL result set.
     * @return string
     */
    public function getScalarAlias($columnName)
    {
        return $this->scalarMappings[$columnName];
    }

    /**
     * Gets the name of the class that owns a field mapping for the specified column.
     *
     * @param string $columnName
     * @return string
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
     * Gets the alias of the class that owns a field mapping for the specified column.
     *
     * @param string $columnName
     * @return string
     */
    public function getEntityAlias($columnName)
    {
        return $this->columnOwnerMap[$columnName];
    }

    /**
     * Gets the parent alias of the given alias.
     *
     * @param string $alias
     * @return string
     */
    public function getParentAlias($alias)
    {
        return $this->parentAliasMap[$alias];
    }

    /**
     * Checks whether the given alias has a parent alias.
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
     * Gets the number of different entities that appear in the mapped result.
     *
     * @return integer
     */
    public function getEntityResultCount()
    {
        return count($this->aliasMap);
    }

    /**
     * Checks whether this ResultSetMapping defines a mixed result.
     * Mixed results can only occur in object and array (graph) hydration. In such a
     * case a mixed result means that scalar values are mixed with objects/array in
     * the result.
     *
     * @return boolean
     */
    public function isMixedResult()
    {
        return $this->isMixed;
    }
    
    /**
     * 
     * @param $alias
     * @param $columnName
     * @param $fieldName
     * @return unknown_type
     */
    public function addMetaResult($alias, $columnName, $fieldName)
    {
        $this->metaMappings[$columnName] = $fieldName;
        $this->columnOwnerMap[$columnName] = $alias;
    }

    /**
     * Adds a column name that will be ignored during hydration.
     *
     * @param string $columnName
     */
    /*public function addIgnoredColumn($columnName)
    {
        $this->ignoredColumns[$columnName] = true;
    }*/

    /**
     *
     * @param string $columnName
     * @return boolean
     */
    /*public function isIgnoredColumn($columnName)
    {
        return isset($this->ignoredColumns[$columnName]);
    }*/
}

