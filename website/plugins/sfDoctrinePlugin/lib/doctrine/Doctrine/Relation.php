<?php
/*
 *  $Id: Relation.php 1973 2007-07-11 14:39:15Z zYne $
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
 * Doctrine_Relation
 * This class represents a relation between components
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1973 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Relation implements ArrayAccess
{
    /**
     * RELATION CONSTANTS
     */

    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE aggregate relationships
     */
    const ONE_AGGREGATE         = 0;
    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE composite relationships
     */
    const ONE_COMPOSITE         = 1;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY aggregate relationships
     */
    const MANY_AGGREGATE        = 2;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY composite relationships
     */
    const MANY_COMPOSITE        = 3;

    const ONE   = 0;
    const MANY  = 2;
    
    protected $definition = array('alias'       => true,
                                  'foreign'     => true,
                                  'local'       => true,
                                  'class'       => true,
                                  'type'        => true,
                                  'table'       => true,
                                  'name'        => false,
                                  'refTable'    => false,
                                  'onDelete'    => false,
                                  'onUpdate'    => false,
                                  'deferred'    => false,
                                  'deferrable'  => false,
                                  'constraint'  => false,
                                  'equal'       => false,
                                  );
    /**
     * constructor
     *
     * @param array $definition         an associative array with the following structure:
     *          name                    foreign key constraint name
     *
     *          local                   the local field(s)
     *
     *          foreign                 the foreign reference field(s)
     *
     *          table                   the foreign table object
     *
     *          refTable                the reference table object (if any)
     *
     *          onDelete                referential delete action
     *  
     *          onUpdate                referential update action
     *
     *          deferred                deferred constraint checking 
     *
     *          alias                   relation alias
     *
     *          type                    the relation type, either Doctrine_Relation::ONE or Doctrine_Relation::MANY
     *
     *          constraint              boolean value, true if the relation has an explicit referential integrity constraint
     *
     * The onDelete and onUpdate keys accept the following values:
     *
     * CASCADE: Delete or update the row from the parent table and automatically delete or
     *          update the matching rows in the child table. Both ON DELETE CASCADE and ON UPDATE CASCADE are supported.
     *          Between two tables, you should not define several ON UPDATE CASCADE clauses that act on the same column
     *          in the parent table or in the child table.
     *
     * SET NULL: Delete or update the row from the parent table and set the foreign key column or columns in the
     *          child table to NULL. This is valid only if the foreign key columns do not have the NOT NULL qualifier 
     *          specified. Both ON DELETE SET NULL and ON UPDATE SET NULL clauses are supported.
     *
     * NO ACTION: In standard SQL, NO ACTION means no action in the sense that an attempt to delete or update a primary 
     *           key value is not allowed to proceed if there is a related foreign key value in the referenced table.
     *
     * RESTRICT: Rejects the delete or update operation for the parent table. NO ACTION and RESTRICT are the same as
     *           omitting the ON DELETE or ON UPDATE clause.
     *
     * SET DEFAULT
     */
    public function __construct(array $definition)
    {
    	$def = array();
    	foreach ($this->definition as $key => $val) {
            if ( ! isset($definition[$key]) && $val) {
                throw new Doctrine_Exception($key . ' is required!');
            }
            if (isset($definition[$key])) {
                $def[$key] = $definition[$key];
            } else {
                $def[$key] = null;      	
            }
        }

        $this->definition = $def;
    }
    /**
     * hasConstraint
     * whether or not this relation has an explicit constraint
     *
     * @return boolean
     */
    public function hasConstraint()
    {
        return ($this->definition['constraint'] ||
                ($this->definition['onUpdate']) ||
                ($this->definition['onDelete']));
    }
    public function isDeferred()
    {
        return $this->definition['deferred'];
    }

    public function isDeferrable()
    {
        return $this->definition['deferrable'];
    }
    public function isEqual()
    {
        return $this->definition['equal'];
    }

    public function offsetExists($offset)
    {
        return isset($this->definition[$offset]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->definition[$offset])) {
            return $this->definition[$offset];
        }
        
        return null;
    }

    public function offsetSet($offset, $value)
    {
        if (isset($this->definition[$offset])) {
            $this->definition[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        $this->definition[$offset] = false;
    }
    /**
     * toArray
     *
     * @return array
     */
    public function toArray() 
    {
        return $this->definition;
    }
    /**
     * getAlias
     * returns the relation alias
     *
     * @return string
     */
    final public function getAlias()
    {
        return $this->definition['alias'];
    }
    /**
     * getType
     * returns the relation type, either 0 or 1
     *
     * @see Doctrine_Relation MANY_* and ONE_* constants
     * @return integer
     */
    final public function getType()
    {
        return $this->definition['type'];
    }
    /**
     * getTable
     * returns the foreign table object
     *
     * @return object Doctrine_Table
     */
    final public function getTable()
    {
        return Doctrine_Manager::getInstance()
               ->getConnectionForComponent($this->definition['class'])
               ->getTable($this->definition['class']);
    }
    /**
     * getLocal
     * returns the name of the local column
     *
     * @return string
     */
    final public function getLocal()
    {
        return $this->definition['local'];
    }
    /**
     * getForeign
     * returns the name of the foreignkey column where
     * the localkey column is pointing at
     *
     * @return string
     */
    final public function getForeign()
    {
        return $this->definition['foreign'];
    }
    /**
     * isComposite
     * returns whether or not this relation is a composite relation
     *
     * @return boolean
     */
    final public function isComposite()
    {
        return ($this->definition['type'] == Doctrine_Relation::ONE_COMPOSITE ||
                $this->definition['type'] == Doctrine_Relation::MANY_COMPOSITE);
    }
    /**
     * isOneToOne
     * returns whether or not this relation is a one-to-one relation
     *
     * @return boolean
     */
    final public function isOneToOne()
    {
        return ($this->definition['type'] == Doctrine_Relation::ONE_AGGREGATE ||
                $this->definition['type'] == Doctrine_Relation::ONE_COMPOSITE);
    }
    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count)
    {
    	$component = $this->getTable()->getComponentName();

        $dql  = 'FROM ' . $component
              . ' WHERE ' . $component . '.' . $this->definition['foreign']
              . ' IN (' . substr(str_repeat('?, ', $count), 0, -2) . ')';

        return $dql;
    }
    /**
     * fetchRelatedFor
     *
     * fetches a component related to given record
     *
     * @param Doctrine_Record $record
     * @return Doctrine_Record|Doctrine_Collection
     */
    abstract public function fetchRelatedFor(Doctrine_Record $record);
    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        $r[] = "<pre>";
        foreach ($this->definition as $k => $v) {
            if(is_object($v)) {
                $v = 'Object(' . get_class($v) . ')';
            }
            $r[] = $k . ' : ' . $v;
        }
        $r[] = "</pre>";
        return implode("\n", $r);
    }
}
