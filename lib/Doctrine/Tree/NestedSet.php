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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Tree_NestedSet
 *
 * @package     Doctrine
 * @subpackage  Tree
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Tree_NestedSet extends Doctrine_Tree implements Doctrine_Tree_Interface
{
    private $_baseQuery;
    private $_baseAlias = "base";

    /**
     * constructor, creates tree with reference to table and sets default root options
     *
     * @param object $table                     instance of Doctrine_Table
     * @param array $options                    options
     */
    public function __construct(Doctrine_Table $table, $options)
    {
        // set default many root attributes
        $options['hasManyRoots'] = isset($options['hasManyRoots']) ? $options['hasManyRoots'] : false;
        if ($options['hasManyRoots']) {
            $options['rootColumnName'] = isset($options['rootColumnName']) ? $options['rootColumnName'] : 'root_id';
        }
        
        parent::__construct($table, $options);
    }

    /**
     * used to define table attributes required for the NestetSet implementation
     * adds lft and rgt columns for corresponding left and right values
     *
     */
    public function setTableDefinition()
    {
        if ($root = $this->getAttribute('rootColumnName')) {
            $this->table->setColumn($root, 'integer', 4);
        }

        $this->table->setColumn('lft', 'integer', 4);
        $this->table->setColumn('rgt', 'integer', 4);
        $this->table->setColumn('level', 'integer', 2);
    }

    /**
     * creates root node from given record or from a new record
     *
     * @param object $record        instance of Doctrine_Record
     */
    public function createRoot(Doctrine_Record $record = null)
    {
        if ( ! $record) {
            $record = $this->table->create();
        }

        // if tree is many roots, and no root id has been set, then get next root id
        if ($root = $this->getAttribute('hasManyRoots') && $record->getNode()->getRootValue() <= 0) {
            $record->getNode()->setRootValue($this->getNextRootId());
        }

        $record->set('lft', '1');
        $record->set('rgt', '2');
        $record->set('level', 0);

        $record->save();

        return $record;
    }

    /**
     * returns root node
     *
     * @return object $record        instance of Doctrine_Record
     * @deprecated Use fetchRoot()
     */
    public function findRoot($rootId = 1)
    {
        return $this->fetchRoot($rootId);
    }

    /**
     * Fetches a/the root node.
     *
     * @param integer $rootId
     */
    public function fetchRoot($rootId = 1)
    {
        $q = $this->getBaseQuery();
        $q = $q->addWhere($this->_baseAlias . '.lft = ?', 1);
        
        // if tree has many roots, then specify root id
        $q = $this->returnQueryWithRootId($q, $rootId);
        $data = $q->execute();

        if (count($data) <= 0) {
            return false;
        }

        if ($data instanceof Doctrine_Collection) {
            $root = $data->getFirst();
            $root['level'] = 0;
        } else if (is_array($data)) {
            $root = array_shift($data);
            $root['level'] = 0;
        } else {
            throw new Doctrine_Tree_Exception("Unexpected data structure returned.");
        }

        return $root;
    }

    /**
     * Fetches a tree.
     *
     * @param array $options  Options
     * @return mixed          The tree or FALSE if the tree could not be found.
     */
    public function fetchTree($options = array())
    {
        // fetch tree
        $q = $this->getBaseQuery();

        $q = $q->addWhere($this->_baseAlias . ".lft >= ?", 1);

        // if tree has many roots, then specify root id
        $rootId = isset($options['root_id']) ? $options['root_id'] : '1';
        if (is_array($rootId)) {
            $q->addOrderBy($this->_baseAlias . "." . $this->getAttribute('rootColumnName') .
                    ", " . $this->_baseAlias . ".lft ASC");
        } else {
            $q->addOrderBy($this->_baseAlias . ".lft ASC");
        }
        
        $q = $this->returnQueryWithRootId($q, $rootId);
        $tree = $q->execute();
        
        if (count($tree) <= 0) {
            return false;
        }
        
        return $tree;
    }

    /**
     * Fetches a branch of a tree.
     *
     * @param mixed $pk              primary key as used by table::find() to locate node to traverse tree from
     * @param array $options         Options.
     * @return mixed                 The branch or FALSE if the branch could not be found.
     * @todo Only fetch the lft and rgt values of the initial record. more is not needed.
     */
    public function fetchBranch($pk, $options = array())
    {
        $record = $this->table->find($pk);
        if ( ! ($record instanceof Doctrine_Record) || !$record->exists()) {
            // TODO: if record doesn't exist, throw exception or similar?
            return false;
        }
        //$depth = isset($options['depth']) ? $options['depth'] : null;
        
        $q = $this->getBaseQuery();
        $params = array($record->get('lft'), $record->get('rgt'));
        $q->addWhere($this->_baseAlias . ".lft >= ? AND " . $this->_baseAlias . ".rgt <= ?", $params)
                ->addOrderBy($this->_baseAlias . ".lft asc");
        $q = $this->returnQueryWithRootId($q, $record->getNode()->getRootValue());
        return $q->execute();
    }

    /**
     * Fetches all root nodes. If the tree has only one root this is the same as
     * fetchRoot().
     *
     * @return mixed  The root nodes.
     */
    public function fetchRoots()
    {
        $q = $this->getBaseQuery();
        $q = $q->addWhere($this->_baseAlias . '.lft = ?', 1);
        return $q->execute();
    }

    /**
     * calculates the next available root id
     *
     * @return integer
     */
    public function getNextRootId()
    {
        return $this->getMaxRootId() + 1;
    }

    /**
     * calculates the current max root id
     *
     * @return integer
     */    
    public function getMaxRootId()
    {      
        $component = $this->table->getComponentName();
        $column    = $this->getAttribute('rootColumnName');

        // cannot get this dql to work, cannot retrieve result using $coll[0]->max
        //$dql = "SELECT MAX(c.$column) FROM $component c";
        
        $dql = 'SELECT c.' . $column . ' FROM ' . $component . ' c ORDER BY c.' . $column . ' DESC LIMIT 1';
  
        $coll = $this->table->getConnection()->query($dql);
  
        $max = $coll[0]->get($column);
  
        $max = !is_null($max) ? $max : 0;
  
        return $max;      
    }

    /**
     * returns parsed query with root id where clause added if applicable
     *
     * @param object    $query    Doctrine_Query
     * @param integer   $root_id  id of destination root
     * @return object   Doctrine_Query
     */
    public function returnQueryWithRootId($query, $rootId = 1)
    {
        if ($root = $this->getAttribute('rootColumnName')) {
            if (is_array($rootId)) {
               $query->addWhere($root . ' IN (' . implode(',', array_fill(0, count($rootId), '?')) . ')',
                       $rootId);
            } else {
               $query->addWhere($root . ' = ?', $rootId); 
            }
        }

        return $query;
    }

    /**
     * Enter description here...
     *
     * @param array $options
     * @return unknown
     */
    public function getBaseQuery()
    {
        if ( ! isset($this->_baseQuery)) {
            $this->_baseQuery = $this->_createBaseQuery();
        }
        return $this->_baseQuery->copy();
    }

    /**
     * Enter description here...
     *
     */
    public function getBaseAlias()
    {
        return $this->_baseAlias;
    }

    /**
     * Enter description here...
     *
     */
    private function _createBaseQuery()
    {
        $this->_baseAlias = "base";
        $q = new Doctrine_Query();
        $q->select($this->_baseAlias . ".*")->from($this->getBaseComponent() . " " . $this->_baseAlias);
        return $q;
    }

    /**
     * Enter description here...
     *
     * @param Doctrine_Query $query
     */
    public function setBaseQuery(Doctrine_Query $query)
    {
        $this->_baseAlias = $query->getRootAlias();
        $query->addSelect($this->_baseAlias . ".lft, " . $this->_baseAlias . ".rgt, ". $this->_baseAlias . ".level");
        if ($this->getAttribute('rootColumnName')) {
            $query->addSelect($this->_baseAlias . "." . $this->getAttribute('rootColumnName'));
        }
        $this->_baseQuery = $query;
    }

    /**
     * Enter description here...
     *
     */
    public function resetBaseQuery()
    {
        $this->_baseQuery = null;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $graph
     */
    /*
    public function computeLevels($tree)
    {
        $right = array();
        $isArray = is_array($tree);
        $rootColumnName = $this->getAttribute('rootColumnName');
        
        for ($i = 0, $count = count($tree); $i < $count; $i++) {
            if ($rootColumnName && $i > 0 && $tree[$i][$rootColumnName] != $tree[$i-1][$rootColumnName]) {
                $right = array();
            }
            
            if (count($right) > 0) {
                while (count($right) > 0 && $right[count($right)-1] < $tree[$i]['rgt']) {
                    //echo count($right);
                    array_pop($right);
                }
            }
     
            if ($isArray) {
                $tree[$i]['level'] = count($right);
            } else {
                $tree[$i]->getNode()->setLevel(count($right));
            }
    
            $right[] = $tree[$i]['rgt'];
        }
        return $tree;
    }
    */
}