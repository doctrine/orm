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
 * Doctrine_Node
 *
 * @package     Doctrine
 * @subpackage  Node
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Node implements IteratorAggregate
{
    /**
     * @param object    $record   reference to associated Doctrine_Entity instance
     */
    protected $record;

    /**
     * @param array     $options
     */
    protected $options;

    /**
     * @param string     $iteratorType  (Pre | Post | Level)
     */
    protected $iteratorType;

    /**
     * @param array     $iteratorOptions
     */
    protected $iteratorOptions;

    /**
     * The tree to which the node belongs.
     *
     * @var unknown_type
     */
    protected $_tree;

    /**
     * contructor, creates node with reference to record and any options
     *
     * @param object $record                    instance of Doctrine_Entity
     * @param array $options                    options
     */
    public function __construct(Doctrine_Entity $record, $options)
    {
        $this->record = $record;
        $this->options = $options;
        
        // Make sure that the tree object of the root component is used in the case
        // of column aggregation inheritance.
        $class = $record->getTable()->getComponentName();
        $table = $record->getTable();
        if ($table->getOption('inheritanceMap')) {
            $subclasses = $table->getSubclasses();
            while (in_array($class, $subclasses)) {
                $class = get_parent_class($class);
            }
        }
        if ($class != $table->getComponentName()) {
            $this->_tree = $table->getConnection()->getTable($class)->getTree();
        } else {
            $this->_tree = $table->getTree();
        }
    }

    /**
     * factory method to return node instance based upon chosen implementation
     *
     * @param object $record                    instance of Doctrine_Entity
     * @param string $impName                   implementation (NestedSet, AdjacencyList, MaterializedPath)
     * @param array $options                    options
     * @return object $options                  instance of Doctrine_Node
     */
    public static function factory(Doctrine_Entity $record, $implName, $options = array())
    {
        $class = 'Doctrine_Node_' . $implName;

        if ( ! class_exists($class)) {
            throw new Doctrine_Node_Exception("The class $class must exist and extend Doctrine_Node");
        }

        return new $class($record, $options);
    }

    /**
     * setter for record attribute
     *
     * @param object $record                    instance of Doctrine_Entity
     */
    public function setRecord(Doctrine_Entity $record)
    {
        $this->record = $record;
    }

    /**
     * getter for record attribute
     *
     * @return object                           instance of Doctrine_Entity
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * convenience function for getIterator
     *
     * @param string $type                      type of iterator (Pre | Post | Level)
     * @param array $options                    options
     */
    public function traverse($type = 'Pre', $options = array())
    {
        return $this->getIterator($type, $options);
    }

    /**
     * get iterator
     *
     * @param string $type                      type of iterator (Pre | Post | Level)
     * @param array $options                    options
     */
    public function getIterator($type = null, $options = null)
    {
        if ($type === null) {
            $type = (isset($this->iteratorType) ? $this->iteratorType : 'Pre');
        }

        if ($options === null) {
            $options = (isset($this->iteratorOptions) ? $this->iteratorOptions : array());
        }

        $implName = $this->record->getTable()->getOption('treeImpl');
        $iteratorClass = 'Doctrine_Node_' . $implName . '_' . ucfirst(strtolower($type)) . 'OrderIterator';

        return new $iteratorClass($this->record, $options);
    }

    /**
     * sets node's iterator type
     *
     * @param int
     */
    public function setIteratorType($type)
    {
        $this->iteratorType = $type;
    }

    /**
     * sets node's iterator options
     *
     * @param int
     */
    public function setIteratorOptions($options)
    {
        $this->iteratorOptions = $options;
    }
}