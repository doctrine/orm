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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Tree_NestedSet extends Doctrine_Tree implements Doctrine_Tree_Interface
{
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
        if($options['hasManyRoots'])
            $options['rootColumnName'] = isset($options['rootColumnName']) ? $options['rootColumnName'] : 'root_id';
  
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
            $this->table->setColumn($root, 'integer', 11);
        }

        $this->table->setColumn('lft', 'integer', 11);
        $this->table->setColumn('rgt', 'integer', 11);
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

        // if tree is many roots, then get next root id
        if($root = $this->getAttribute('hasManyRoots')) {
            $record->getNode()->setRootValue($this->getNextRootId());
        }

        $record->set('lft', '1');
        $record->set('rgt', '2');

        $record->save();

        return $record;
    }

    /**
     * returns root node
     *
     * @return object $record        instance of Doctrine_Record
     */
    public function findRoot($rootId = 1)
    {
        $q = $this->table->createQuery();
        $q = $q->where('lft = ?', 1);
        
        // if tree has many roots, then specify root id
        $q = $this->returnQueryWithRootId($q, $rootId);

        $root = $q->execute()->getFirst();

        // if no record is returned, create record
        if ( ! $root) {
            $root = $this->table->create();
        }

        // set level to prevent additional query to determine level
        $root->getNode()->setLevel(0);

        return $root;
    }

    /**
     * optimised method to returns iterator for traversal of the entire tree from root
     *
     * @param array $options                    options
     * @return object $iterator                 instance of Doctrine_Node_NestedSet_PreOrderIterator
     */
    public function fetchTree($options = array())
    {
        // fetch tree
        $q = $this->table->createQuery();
        $componentName = $this->table->getComponentName();

        $q = $q->where("$componentName.lft >= ?", 1)
                ->orderBy("$componentName.lft asc");

        // if tree has many roots, then specify root id
        $rootId = isset($options['root_id']) ? $options['root_id'] : '1';
        $q = $this->returnQueryWithRootId($q, $rootId);
        
        $tree = $q->execute();

        $root = $tree->getFirst();

        // if no record is returned, create record
        if ( ! $root) {
            $root = $this->table->create();
        }

        if ($root->exists()) {
            // set level to prevent additional query
            $root->getNode()->setLevel(0);

            // default to include root node
            $options = array_merge(array('include_record'=>true), $options);

            // remove root node from collection if not required
            if ($options['include_record'] == false) {
                $tree->remove(0);
            }

            // set collection for iterator
            $options['collection'] = $tree;

            return $root->getNode()->traverse('Pre', $options);
        }

        // TODO: no default return value or exception thrown?
    }

    /**
     * optimised method that returns iterator for traversal of the tree from the given record primary key
     *
     * @param mixed $pk                         primary key as used by table::find() to locate node to traverse tree from
     * @param array $options                    options
     * @return iterator                         instance of Doctrine_Node_<Implementation>_PreOrderIterator
     */
    public function fetchBranch($pk, $options = array())
    {
        $record = $this->table->find($pk);
        if ($record->exists()) {
            $options = array_merge(array('include_record'=>true), $options);
            return $record->getNode()->traverse('Pre', $options);
        }

        // TODO: if record doesn't exist, throw exception or similar?
    }

    /**
     * fetch root nodes
     *
     * @return collection                         Doctrine_Collection
     */
    public function fetchRoots()
    {
        $q = $this->table->createQuery();
        $q = $q->where('lft = ?', 1);
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
            $query->addWhere($root . ' = ?', $rootId);
        }

        return $query;
    }
}
