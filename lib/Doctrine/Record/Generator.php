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
 * Doctrine_Record_Generator
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @subpackage  Plugin
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
abstract class Doctrine_Record_Generator extends Doctrine_Record_Abstract
{
    /**
     * @var array $_options     an array of plugin specific options
     */
    protected $_options = array('generateFiles' => false,
                                'identifier'    => false,
                                'generateFiles' => false,
                                'table'         => false,
                                'pluginTable'   => false,
                                'children'      => array());
                                
    protected $_initialized = false;
    /**
     * __get
     * an alias for getOption
     *
     * @param string $option
     */
    public function __get($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        return null;
    }

    /**
     * __isset
     *
     * @param string $option
     */
    public function __isset($option) 
    {
        return isset($this->_options[$option]);
    }

    /**
     * returns the value of an option
     *
     * @param $option       the name of the option to retrieve
     * @return mixed        the value of the option
     */
    public function getOption($name)
    {
        if ( ! isset($this->_options[$name])) {
            throw new Doctrine_Plugin_Exception('Unknown option ' . $name);
        }
        
        return $this->_options[$name];
    }

    /**
     * sets given value to an option
     *
     * @param $option       the name of the option to be changed
     * @param $value        the value of the option
     * @return Doctrine_Plugin  this object
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
        
        return $this;
    }

    public function addChild(Doctrine_Record_Generator $template)
    {
        $this->_options['children'][] = $template;
    }

    /**
     * returns all options and their associated values
     *
     * @return array    all options as an associative array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    public function initialize($table)
    {
    	if ($this->_initialized) {
    	    return false;
    	}
        
        $this->_initialized = true;

        $this->initOptions();

        $table->addGenerator($this, get_class($this));

        $this->_options['table'] = $table;

        $this->_options['className'] = str_replace('%CLASS%',
                                                   $this->_options['table']->getComponentName(),
                                                   $this->_options['className']);

        // check that class doesn't exist (otherwise we cannot create it)
        if (class_exists($this->_options['className'])) {
            return false;
        }

        $conn = $this->_options['table']->getConnection();

        $this->_table = new Doctrine_Table($this->_options['className'], $conn);
        
        $conn->addTable($this->_table);

        $fk = $this->buildForeignKeys($this->_options['table']);
        
        $this->_table->setColumns($fk);

        $this->buildRelation();

        $this->setTableDefinition();
        $this->setUp();

        $this->generateClass($this->_table->getColumns());

        $this->buildChildDefinitions();

    }
    /** 
     * empty template method for providing the concrete plugins the ability
     * to initialize options before the actual definition is being built
     *
     * @return void
     */
    public function initOptions()
    {
        
    }
    public function buildChildDefinitions()
    {
        if ( ! isset($this->_options['children'])) {
            throw new Doctrine_Plugin_Exception("Unknown option 'children'.");
        }

        foreach ($this->_options['children'] as $child) {
            $this->_table->addGenerator($child, get_class($child));

            $child->setTable($this->_table);

            $child->setUp();
        }
    }

    /**
     * generates foreign keys for the plugin table based on the owner table
     *
     * the foreign keys generated by this method can be used for 
     * setting the relations between the owner and the plugin classes
     *
     * @param Doctrine_Table $table     the table object that owns the plugin
     * @return array                    an array of foreign key definitions
     */
    public function buildForeignKeys($table)
    {
        $fk = array();

        foreach ((array) $table->getIdentifier() as $column) {
            $def = $table->getDefinitionOf($column);

            unset($def['autoincrement']);
            unset($def['sequence']);
            unset($def['primary']);

            $col = $column;

            $def['primary'] = true;
            $fk[$col] = $def;
        }
        return $fk;
    }

    public function buildLocalRelation()
    {
        $options = array('local'    => $this->_options['table']->getIdentifier(),
                         'foreign'  => $this->_options['table']->getIdentifier(),
                         'type'     => Doctrine_Relation::MANY);

        $options['type'] = Doctrine_Relation::ONE;
        $options['onDelete'] = 'CASCADE';
        $options['onUpdate'] = 'CASCADE';

        $this->_table->getRelationParser()->bind($this->_options['table']->getComponentName(), $options);
    }
    
    public function buildForeignRelation($alias = null)
    {
        $options = array('local'    => $this->_options['table']->getIdentifier(),
                         'foreign'  => $this->_options['table']->getIdentifier(),
                         'type'     => Doctrine_Relation::MANY);

        $aliasStr = '';

        if ($alias !== null) {
            $aliasStr = ' as ' . $alias;
        }

        $this->_options['table']->getRelationParser()->bind($this->_table->getComponentName() . $aliasStr,
                                                            $options);
    }

    /**
     * build a relation array to given table
     *
     * this method can be used for generating the relation from the plugin 
     * table to the owner table
     *
     * @return array                    the generated relation array
     */
    public function buildRelation()
    {
    	$this->buildForeignRelation();
        $this->buildLocalRelation();
    }

    /**
     * generates the class definition for plugin class
     *
     * @param array $columns    the plugin class columns, keys representing the column names
     *                          and values as column definitions
     *
     * @param array $relations  the bound relations of the plugin class
     *
     * @param array $options    plugin class options, keys representing the option names
     *                          and values as option values
     * @return void
     */
    public function generateClass(array $columns = array(), array $relations = array(), array $options = array())
    {
        $options['className'] = $this->_options['className'];

        $builder = new Doctrine_Builder_Record();

        if ($this->_options['generateFiles']) {
            if (isset($this->_options['generatePath']) && $this->_options['generatePath']) {
                $builder->setTargetPath($this->_options['generatePath']);

                $builder->buildRecord($options, $columns, $relations);
            } else {
                throw new Doctrine_Record_Exception('If you wish to generate files then you must specify the path to generate the files in.');
            }
        } else {
            $def = $builder->buildDefinition($options, $columns, $relations);

            eval($def);
        }
    }
}
