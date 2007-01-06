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
     * used to define table attributes required for the NestetSet implementation
     * adds lft and rgt columns for corresponding left and right values
     *
     */
    public function setTableDefinition()
    {
        $this->table->setColumn("lft","integer",11);
        $this->table->setColumn("rgt","integer",11);
    }

    /**
     * creates root node from given record or from a new record
     *
     * @param object $record        instance of Doctrine_Record
     */
    public function createRoot(Doctrine_Record $record = null)
    {
        if (!$record) {
            $record = $this->table->create();
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
    public function findRoot()
    {
        $q = $this->table->createQuery();
        $root = $q->where('lft = ?', 1)
                  ->execute()->getFirst();

        // if no record is returned, create record
        if (!$root) {
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

        $tree = $q->where('lft >= ?', 1)
                  ->orderBy('lft asc')
                  ->execute();

        $root = $tree->getFirst();

        // if no record is returned, create record
        if (!$root) {
            $root = $this->table->create();
        }

        if ($root->exists()) {
            // set level to prevent additional query
            $root->getNode()->setLevel(0);

            // default to include root node
            $options = array_merge(array('include_record'=>true), $options);

            // remove root node from collection if not required
            if($options['include_record'] == false)
              $tree->remove(0);

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
}
