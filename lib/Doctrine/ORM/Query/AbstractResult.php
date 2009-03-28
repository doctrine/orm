<?php
/*
 *  $Id: Cache.php 3938 2008-03-06 19:36:50Z romanb $
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

use Doctrine\Common\DoctrineException;

/**
 * Doctrine_ORM_Query_AbstractResult
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision: 1393 $
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class AbstractResult
{
    /**
     * @var mixed $_data The actual data to be stored. Can be an array, a string or an integer.
     */
    protected $_data;

    /**
     * @var array $_queryComponents
     *
     * Two dimensional array containing the map for query aliases. Main keys are component aliases.
     *
     * table    Table object associated with given alias.
     * relation Relation object owned by the parent.
     * parent   Alias of the parent.
     * agg      Aggregates of this component.
     * map      Name of the column / aggregate value this component is mapped to a collection.
     */
    protected $_queryComponents;

    /**
     * @var array Table alias map. Keys are SQL aliases and values DQL aliases.
     */
    protected $_tableAliasMap;

    /**
     * @var array Enum params.
     */
    protected $_enumParams;

    /**
     * @var string
     */
    protected $_defaultQueryComponentAlias;

    /**
     * @var boolean
     */
    protected $_isMixedQuery = false;

    /**
     * Cannot be called directly, factory methods handle this job.
     *
     * @param mixed $data Data to be stored.
     * @param array $queryComponents Query components.
     * @param array $tableAliasMap Table aliases.
     * @param array $enumParams Enum params.
     * @return Doctrine_ORM_Query_CacheHandler
     */
    public function __construct($data = '', $queryComponents = array(), $tableAliasMap = array(), $enumParams = array())
    {
        $this->_data = $data;
        $this->_queryComponents = $queryComponents;
        $this->_tableAliasMap = $tableAliasMap;
        $this->_enumParams = $enumParams;
    }

    /**
     * Defines the mapping components.
     *
     * @param array $queryComponents Query components.
     */
    public function setQueryComponents(array $queryComponents)
    {
        $this->_queryComponents = $queryComponents;
    }

    /**
     * Sets the declaration for given component alias.
     *
     * @param string $componentAlias The component alias to set the declaration to.
     * @param string $queryComponent Alias declaration.
     */
    public function setQueryComponent($componentAlias, array $queryComponent)
    {
        $this->_queryComponents[$componentAlias] = $queryComponent;
    }

    /**
     * Gets the mapping components.
     *
     * @return array Query components.
     */
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    /**
     *
     */
    public function getDefaultQueryComponentAlias()
    {
        return $this->_defaultQueryComponentAlias;
    }

    /**
     * 
     *
     * @param <type> $alias
     */
    public function setDefaultQueryComponentAlias($alias)
    {
        $this->_defaultQueryComponentAlias = $alias;
    }

    /**
     * Get the declaration for given component alias.
     *
     * @param string $componentAlias The component alias the retrieve the declaration from.
     * @return array Alias declaration.
     */
    public function getQueryComponent($componentAlias)
    {
        if ( ! isset($this->_queryComponents[$componentAlias])) {
            throw new DoctrineException('Unknown query component ' . $componentAlias);
        }

        return $this->_queryComponents[$componentAlias];
    }

    /**
     * Get the component alias for a given query component
     *
     * @param array $queryComponent The query component
     * @param string Component alias
     */
    public function getComponentAlias($queryComponent)
    {
        return array_search($queryComponent, $this->_queryComponents);;
    }

    /**
     * Whether or not this object has a declaration for given component alias.
     *
     * @param string $componentAlias Component alias the retrieve the declaration from.
     * @return boolean True if this object has given alias, otherwise false.
     */
    public function hasQueryComponent($componentAlias)
    {
        return isset($this->_queryComponents[$componentAlias]);
    }

    /**
     * Defines the table aliases.
     *
     * @param array $tableAliasMap Table aliases.
     */
    public function setTableAliasMap(array $tableAliasMap)
    {
        $this->_tableAliasMap = $tableAliasMap;
    }

    /**
     * Adds an SQL table alias and associates it a component alias
     *
     * @param string $tableAlias Table alias to be added.
     * @param string $componentAlias Alias for the query component associated with given tableAlias.
     */
    public function setTableAlias($tableAlias, $componentAlias)
    {
        $this->_tableAliasMap[$tableAlias] = $componentAlias;
    }

    /**
     * Returns all table aliases.
     *
     * @return array Table aliases as an array.
     */
    public function getTableAliasMap()
    {
        return $this->_tableAliasMap;
    }

    /**
     * Get DQL alias associated with given SQL table alias.
     *
     * @param string $tableAlias SQL table alias that identifies the component alias
     * @return string Component alias
     */
    public function getTableAlias($tableAlias)
    {
        if ( ! isset($this->_tableAliasMap[$tableAlias])) {
            throw DoctrineException::updateMe('Unknown table alias ' . $tableAlias);
        }

        return $this->_tableAliasMap[$tableAlias];
    }

    /**
     * Get table alias associated with given component alias.
     *
     * @param string $componentAlias Component alias that identifies the table alias
     * @return string Component alias
     */
    public function getTableAliasFromComponentAlias($componentAlias)
    {
        return array_search($componentAlias, $this->_tableAliasMap);
    }

    /**
     * Whether or not this object has given tableAlias.
     *
     * @param string $tableAlias Table alias to be checked.
     * @return boolean True if this object has given alias, otherwise false.
     */
    public function hasTableAlias($tableAlias)
    {
        return (isset($this->_tableAliasMap[$tableAlias]));
    }

    /**
     * Gets whether the parsed query selects objects/arrays and scalar values
     * at the same time.
     *
     * @return boolean
     */
    public function isMixedQuery()
    {
        return $this->_isMixedQuery;
    }

    /**
     * Sets whether the parsed query selects objects/arrays and scalar values
     * at the same time.
     */
    public function setMixedQuery($bool)
    {
        $this->_isMixedQuery = $bool;
    }

    /**
     * Returns the enum parameters.
     *
     * @return mixed Enum parameters.
     */
    public function getEnumParams()
    {
        return $this->_enumParams;
    }

    /**
     * Sets input parameter as an enumerated parameter
     *
     * @param string $key The key of the input parameter
     * @return Doctrine_ORM_Query_AbstractResult
     */
    public function addEnumParam($key, $table = null, $column = null)
    {
        $array = (isset($table) || isset($column)) ? array($table, $column) : array();

        if ($key === '?') {
            $this->_enumParams[] = $array;
        } else {
            $this->_enumParams[$key] = $array;
        }

        return $this;
    }

    /**
     * Returns this object in serialized format, revertable using fromCached*.
     *
     * @return string Serialized cached item.
     */
    public function toCachedForm()
    {
        return serialize(array(
            $this->_data,
            $this->getQueryComponents(),
            $this->getTableAliasMap(),
            $this->getEnumParams()
        ));
    }
}