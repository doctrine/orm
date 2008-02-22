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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Node_NestedSet_PreOrderIterator
 *
 * @package     Doctrine
 * @subpackage  Node
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Node_NestedSet_PreOrderIterator implements Iterator
{
    /**
     * @var Doctrine_Collection $collection
     */
    protected $collection;

    /**
     * @var array $keys
     */
    protected $keys;

    /**
     * @var mixed $key
     */
    protected $key;

    /**
     * @var integer $index
     */
    protected $index;

    /**
     * @var integer $index
     */
    protected $prevIndex;

    /**
     * @var integer $index
     */
    protected $traverseLevel;

    /**
     * @var integer $count
     */
    protected $count;

    public function __construct($record, $opts)
    {
        $componentName = $record->getTable()->getComponentName();

        $q = $record->getTable()->createQuery();

        $params = array($record->get('lft'), $record->get('rgt'));
        if (isset($opts['include_record']) && $opts['include_record']) {
            $query = $q->where("$componentName.lft >= ? AND $componentName.rgt <= ?", $params)->orderBy("$componentName.lft asc");
        } else {
            $query = $q->where("$componentName.lft > ? AND $componentName.rgt < ?", $params)->orderBy("$componentName.lft asc");
        }
        
        $query = $record->getTable()->getTree()->returnQueryWithRootId($query, $record->getNode()->getRootValue());

        $this->maxLevel   = isset($opts['depth']) ? ($opts['depth'] + $record->getNode()->getLevel()) : 0;
        $this->options    = $opts;
        $this->collection = isset($opts['collection']) ? $opts['collection'] : $query->execute();
        $this->keys       = $this->collection->getKeys();
        $this->count      = $this->collection->count();
        $this->index      = -1;
        $this->level      = $record->getNode()->getLevel();
        $this->prevLeft   = $record->getNode()->getLeftValue();

        // clear the table identity cache
        $record->getTable()->clear();
    }

    /**
     * rewinds the iterator
     *
     * @return void
     */
    public function rewind()
    {
        $this->index = -1;
        $this->key = null;
    }

    /**
     * returns the current key
     *
     * @return integer
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * returns the current record
     *
     * @return Doctrine_Record
     */
    public function current()
    {
        $record = $this->collection->get($this->key);
        $record->getNode()->setLevel($this->level);
        return $record;
    }

    /**
     * advances the internal pointer
     *
     * @return void
     */
    public function next()
    {
        while ($current = $this->advanceIndex()) {
            if ($this->maxLevel && ($this->level > $this->maxLevel)) {
                continue;
            }

            return $current;
        }

        return false;
    }

    /**
     * @return boolean                          whether or not the iteration will continue
     */
    public function valid()
    {
        return ($this->index < $this->count);
    }

    public function count()
    {
        return $this->count;
    }

    private function updateLevel()
    {
        if ( ! (isset($this->options['include_record']) && $this->options['include_record'] && $this->index == 0)) {
            $left = $this->collection->get($this->key)->getNode()->getLeftValue();
            $this->level += $this->prevLeft - $left + 2;
            $this->prevLeft = $left;
        }
    }

    private function advanceIndex()
    {
        $this->index++;
        $i = $this->index;
        if (isset($this->keys[$i])) {
            $this->key   = $this->keys[$i];
            $this->updateLevel();
            return $this->current();
        }

        return false;
    }
}