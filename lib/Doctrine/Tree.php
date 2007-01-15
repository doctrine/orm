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
 * Doctrine_Tree
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Joe Simms <joe.simms@websites4.com>
 */
class Doctrine_Tree
{
    /**
     * @param object $table   reference to associated Doctrine_Table instance
     */
    protected $table;

    /**
     * @param array $options
     */
    protected $options = array();

    /**
     * constructor, creates tree with reference to table and any options
     *
     * @param object $table                     instance of Doctrine_Table
     * @param array $options                    options
     */
    public function __construct(Doctrine_Table $table, $options)
    {
        $this->table = $table;
        $this->options = $options;
    }

    /**
     * Used to define table attributes required for the given implementation
     *
     * @throws Doctrine_Tree_Exception          if table attributes have not been defined
     */
    public function setTableDefinition()
    {
        throw new Doctrine_Tree_Exception('Table attributes have not been defined for this Tree implementation.');
    }

    /**
     * this method is used for setting up relations and attributes and should be used by specific implementations
     *
     */
    public function setUp()
    {
    }

    /**
     * factory method to return tree instance based upon chosen implementation
     *
     * @param object $table                     instance of Doctrine_Table
     * @param string $impName                   implementation (NestedSet, AdjacencyList, MaterializedPath)
     * @param array $options                    options
     * @return object $options                  instance of Doctrine_Node
     * @throws Doctrine_Exception               if class does not extend Doctrine_Tree
     */
    public static function factory(Doctrine_Table $table, $implName, $options = array())
    {
        $class = 'Doctrine_Tree_' . $implName;
        if (!class_exists($class)) {
            throw new Doctrine_Exception('The chosen class must extend Doctrine_Tree');
        }
        return new $class($table, $options);
    }

    /**
     * gets tree attribute value
     *        
     */     
    public function getAttribute($name)
    {
      return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * sets tree attribute value
     *
     * @param mixed            
     */
    public function setAttribute($name, $value)
    {
      $this->options[$name] = $value;
    }
}
