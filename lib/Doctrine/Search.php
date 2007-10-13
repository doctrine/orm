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
 * Doctrine_Search
 *
 * @package     Doctrine
 * @subpackage  Search
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Search
{
    protected $_options = array('generateFiles' => false,
                                'className'     => '%CLASS%Index',
                                'generatePath'  => false);

    
    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        
        if ( ! isset($this->_options['analyzer'])) {
            $this->_options['analyzer'] = new Doctrine_Search_Analyzer_Standard();
        }
    }

    public function getOption($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        
        throw new Doctrine_Search_Exception('Unknown option ' . $option);
    }
    
    public function analyze($text)
    {
        return $this->_options['analyzer']->analyze($text);
    }

    public function setOption($option, $value)
    {
        $this->_options[$option] = $value;

        return $this;
    }
    /**
     * updateIndex
     * updates the index
     *
     * @param Doctrine_Record $record
     * @return integer
     */
    public function updateIndex(Doctrine_Record $record) 
    {
        $fields = $this->getOption('fields');
        $class  = $this->getOption('className');
        $name   = $record->getTable()->getComponentName();

        foreach ($fields as $field) {
            $data  = $record->get($field);

            $terms = $this->analyze($data);

            foreach ($terms as $pos => $term) {
                $index = new $class();

                $index->keyword = $term;
                $index->position = $pos;
                $index->field = $field;
                $index->$name = $record;
                
                $index->save();
            }
        }
    }
    
    public function processPendingTable($tableName, $indexTableName, array $fields, $id, $conn = null)
    {
        if ( ! ($conn instanceof Doctrine_Connection)) {
            $conn = Doctrine_Manager::connection();
        }
        $fields = array_merge($fields, array($id));
        $query = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $tableName . ' WHERE '
               . $id . ' IN (SELECT foreign_id FROM ' 
               . $indexTableName 
               . ') ORDER BY ' . $id;

        $data = $conn->fetchAll($query);

        foreach ($data as $row) {
            $identifier = $row[$id];

            unset($row[$id]);

            foreach ($row as $field => $data) {
                $terms = $this->analyze($data);

                foreach ($terms as $pos => $term) {
                    $conn->insert($indexTableName, array('keyword'    => $field,
                                                         'position'   => $pos,
                                                         'field'      => $field,
                                                         'foreign_id' => $identifier));
                }
            }
        }
    }
    /**
     * insertPending
     *
     * @return integer
     */
    public function insertPending($indexTableName, $id, $conn = null)
    {
        if ( ! ($conn instanceof Doctrine_Connection)) {
            $conn = Doctrine_Manager::connection();
        }

        $conn->insert($indexTableName, array('foreign_id' => $id));
    }
    public function buildDefinition(Doctrine_Table $table)
    {
        $name = $table->getComponentName();

        $className = $this->getOption('className');
        
        if (class_exists($className)) {
            return false;
        }

        $columns = array('keyword'  => array('type'    => 'string',
                                             'length'  => 200,
                                             'notnull' => true,
                                             'primary' => true,
                                             ),
                         'field'    => array('type'    => 'string',
                                             'length'  => 50,
                                             'notnull' => true,
                                             'primary' => true),
                         'position' => array('type'    => 'integer',
                                             'length'  => 8,
                                             'primary' => true,
                                             ));

        $id = $table->getIdentifier();

        $options = array('className' => $className);


        $fk = array();
        foreach ((array) $id as $column) {
            $def = $table->getDefinitionOf($column);

            unset($def['autoincrement']);
            unset($def['sequence']);
            unset($def['primary']);

            $col = strtolower(Doctrine::tableize($name) . '_' . $column);

            $def['primary'] = true;
            $fk[$col] = $def;
        }
        
        $local = (count($fk) > 1) ? array_keys($fk) : key($fk);
        
        $relations = array($name => array('local' => $local,
                                          'foreign' => $id, 
                                          'onDelete' => 'CASCADE',
                                          'onUpdate' => 'CASCADE'));


        $columns += $fk;

        $builder = new Doctrine_Import_Builder();
    
        if ($this->_options['generateFiles']) {
            if (isset($this->_options['generatePath']) && $this->_options['generatePath']) {
                $builder->setTargetPath($this->_options['generatePath']);
            
                $builder->buildRecord($options, $columns, $relations);
            } else {
                throw new Doctrine_Search_Exception('If you wish to generate files then you must specify the path to generate the files in.');
            }
        } else {
            $def = $builder->buildDefinition($options, $columns, $relations);
          
            eval($def);
        }
        
        return true;
    }
}
