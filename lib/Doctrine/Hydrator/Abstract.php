<?php
/*
 *  $Id: Hydrate.php 3192 2007-11-19 17:55:23Z romanb $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Base class for all hydrators (ok, we got only 1 currently).
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3192 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class Doctrine_Hydrator_Abstract
{
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
    protected $_queryComponents = array();

    /**
     * @var array Table alias map. Keys are SQL aliases and values DQL aliases.
     */
    protected $_tableAliasMap = array();

    /**
     * The current hydration mode.
     */
    protected $_hydrationMode = Doctrine::HYDRATE_RECORD;
    
    protected $_nullObject;
    
    protected $_em;


    /**
     * constructor
     *
     * @param Doctrine_Connection|null $connection
     */
    public function __construct(Doctrine_EntityManager $em)
    {
        $this->_em = $em;
        $this->_nullObject = Doctrine_Null::$INSTANCE;
    }

    /**
     * setHydrationMode
     *
     * Defines the hydration process mode.
     *
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Doctrine::HYDRATE_* constants.
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->_hydrationMode = $hydrationMode;
    }

    /**
     * setQueryComponents
     *
     * Defines the mapping components.
     *
     * @param array $queryComponents Query components.
     */
    public function setQueryComponents(array $queryComponents)
    {
        $this->_queryComponents = $queryComponents;
    }

    /**
     * getQueryComponents
     *
     * Gets the mapping components.
     *
     * @return array Query components.
     */
    public function getQueryComponents()
    {
        return $this->_queryComponents;
    }

    /**
     * setTableAliasMap
     *
     * Defines the table aliases.
     *
     * @param array $tableAliasMap Table aliases.
     */
    public function setTableAliasMap(array $tableAliasMap)
    {
        $this->_tableAliasMap = $tableAliasMap;
    }

    /**
     * getTableAliasMap
     *
     * Returns all table aliases.
     *
     * @return array Table aliases as an array.
     */
    public function getTableAliasMap()
    {
        return $this->_tableAliasMap;
    }

    /**
     * hydrateResultSet
     *
     * Processes data returned by statement object.
     *
     * This is method defines the core of Doctrine object population algorithm
     * hence this method strives to be as fast as possible.
     *
     * The key idea is the loop over the rowset only once doing all the needed operations
     * within this massive loop.
     *
     * @param mixed $stmt PDOStatement
     * @param integer $hydrationMode Doctrine processing mode to be used during hydration process.
     *                               One of the Doctrine::HYDRATE_* constants.
     * @return mixed Doctrine_Collection|array
     */
    abstract public function hydrateResultSet($parserResult);
}
