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
class Doctrine_Search extends Doctrine_Plugin
{
    protected $_options = array('generateFiles' => false,
                                'className'     => '%CLASS%Index',
                                'generatePath'  => false,
                                'batchUpdates'  => false,
                                'pluginTable'   => false);

    
    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        
        if ( ! isset($this->_options['analyzer'])) {
            $this->_options['analyzer'] = new Doctrine_Search_Analyzer_Standard();
        }
    }

    
    public function analyze($text)
    {
        return $this->_options['analyzer']->analyze($text);
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

        if ($this->_options['batchUpdates'] === true) {

            $conn = $record->getTable()->getConnection();
            
            $index = new $class(); 
            foreach ($record->identifier() as $id => $value) {
                $index->$id = $value;
            }
            
            $index->save();
        } else {
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
                                             'primary' => true,
                                             ),
                         'field'    => array('type'    => 'string',
                                             'length'  => 50,
                                             'primary' => true),
                         'position' => array('type'    => 'integer',
                                             'length'  => 8,
                                             'primary' => true,
                                             ));

        $id = $table->getIdentifier();

        $options = array('className' => $className);
        
        $fk = $this->generateForeignKeys($table);
        $columns += $fk;

        $relations = $this->generateRelation($table, $fk);

        $this->generateClass($options, $columns, $relations);

        $this->_options['pluginTable'] = $table->getConnection()->getTable($this->_options['className']);

        return true;
    }
}
