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
Doctrine::autoload('Doctrine_Access');
/**
 * Doctrine_Record_Abstract
 *
 * @package     Doctrine
 * @subpackage  Record
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
abstract class Doctrine_Record_Abstract extends Doctrine_Access
{
    /**
     * @param Doctrine_Table $_table     reference to associated Doctrine_Table instance
     */
    protected $_table;
    /**
     * addListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Record
     */
    public function addListener($listener, $name = null)
    {
        $this->_table->addRecordListener($listener, $name = null);

        return $this;
    }
    /**
     * getListener
     *
     * @return Doctrine_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener()
    {
        return $this->_table->getRecordListener();
    }
    /**
     * setListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Record
     */
    public function setListener($listener)
    {
        $this->_table->setRecordListener($listener);

        return $this;
    }
    /**
     * index
     * defines or retrieves an index
     * if the second parameter is set this method defines an index
     * if not this method retrieves index named $name
     *
     * @param string $name              the name of the index
     * @param array $definition         the definition array
     * @return mixed
     */
    public function index($name, array $definition = array())
    {
        if ( ! $definition) {
            return $this->_table->getIndex($name);
        } else {
            return $this->_table->addIndex($name, $definition);
        }
    }
    public function setAttribute($attr, $value)
    {
        $this->_table->setAttribute($attr, $value);
    }
    public function setTableName($tableName)
    {
        $this->_table->setOption('tableName', $tableName);
    }
    public function setInheritanceMap($map)
    {
        $this->_table->setOption('inheritanceMap', $map);
    }

    public function setSubclasses($map)
    {
        if (isset($map[get_class($this)])) {
            $this->_table->setOption('inheritanceMap', $map[get_class($this)]);
            return;
        }
        $this->_table->setOption('subclasses', array_keys($map));
        $conn = $this->_table->getConnection(); 
        foreach ($map as $key => $value) {
            $table = $conn->getTable($key);
            $table->setOption('inheritanceMap', $value);
        }
    }

    /**
     * attribute
     * sets or retrieves an option
     *
     * @see Doctrine::ATTR_* constants   availible attributes
     * @param mixed $attr
     * @param mixed $value
     * @return mixed
     */
    public function attribute($attr, $value)
    {
        if ($value == null) {
            if (is_array($attr)) {
                foreach ($attr as $k => $v) {
                    $this->_table->setAttribute($k, $v);
                }
            } else {
                return $this->_table->getAttribute($attr);
            }
        } else {
            $this->_table->setAttribute($attr, $value);
        }    
    }
    /**
     * option
     * sets or retrieves an option
     *
     * @see Doctrine_Table::$options    availible options
     * @param mixed $name               the name of the option
     * @param mixed $value              options value
     * @return mixed
     */
    public function option($name, $value = null)
    {
        if ($value === null) {
            if (is_array($name)) {
                foreach ($name as $k => $v) {
                    $this->_table->setOption($k, $v);
                }
            } else {
                return $this->_table->getOption($name);
            }
        } else {
            $this->_table->setOption($name, $value);
        }
    }
    /**
     * ownsOne
     * binds One-to-One composite relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function ownsOne()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::ONE_COMPOSITE);
        
        return $this;
    }
    /**
     * ownsMany
     * binds One-to-Many / Many-to-Many composite relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function ownsMany()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::MANY_COMPOSITE);
        return $this;
    }
    /**
     * hasOne
     * binds One-to-One aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function hasOne()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::ONE_AGGREGATE);

        return $this;
    }
    /**
     * hasMany
     * binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param string $componentName     the name of the related component
     * @param string $options           relation options
     * @see Doctrine_Relation::_$definition
     * @return Doctrine_Record          this object
     */
    public function hasMany()
    {
        $this->_table->bind(func_get_args(), Doctrine_Relation::MANY_AGGREGATE);

        return $this;
    }
    /**
     * hasColumn
     * sets a column definition
     *
     * @param string $name
     * @param string $type
     * @param integer $length
     * @param mixed $options
     * @return void
     */
    public function hasColumn($name, $type, $length = 2147483647, $options = "")
    {
        $this->_table->setColumn($name, $type, $length, $options);
    }
    public function hasColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->hasColumn($name, $options['type'], $options['length'], $options);
        }
    } 
    /**
     * loadTemplate
     *
     * @param string $template
     */
    public function loadTemplate($template, array $options = array())
    {
        $this->actAs($template, $options);
    }
    /**
     * bindQueryParts
     * binds query parts to given component
     *
     * @param array $queryParts         an array of pre-bound query parts
     * @return Doctrine_Record          this object
     */
    public function bindQueryParts(array $queryParts)
    {
    	$this->_table->bindQueryParts(array $queryParts);

        return $this;
    }
    /**
     * actAs
     * loads a given plugin 
     *
     * @param mixed $tpl
     * @param array $options
     */
    public function actAs($tpl, array $options = array())
    {

        if ( ! is_object($tpl)) {
            if (class_exists($tpl, true)) {
                $tpl = new $tpl($options);
            } else {
                $className = 'Doctrine_Template_' . $tpl;

                if ( ! class_exists($className, true)) {
                    throw new Doctrine_Record_Exception("Couldn't load plugin.");
                }


                $tpl = new $className($options);
            }
        }

        if ( ! ($tpl instanceof Doctrine_Template)) {
            throw new Doctrine_Record_Exception('Loaded plugin class is not an istance of Doctrine_Template.');
        }
        $className = get_class($tpl);
        
        $this->_table->addTemplate($className, $tpl);

        $tpl->setTable($this->_table);
        $tpl->setUp();
        $tpl->setTableDefinition();

        return $this;
    }
    /**
     * check
     * adds a check constraint
     *
     * @param mixed $constraint     either a SQL constraint portion or an array of CHECK constraints
     * @param string $name          optional constraint name
     * @return Doctrine_Record      this object
     */
    public function check($constraint, $name = null)
    {
        if (is_array($constraint)) {
            foreach ($constraint as $name => $def) {
                $this->_table->addCheckConstraint($def, $name);
            }
        } else {
            $this->_table->addCheckConstraint($constraint, $name);
        }
        return $this;
    }
}
