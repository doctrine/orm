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
 * Doctrine_Search
 *
 * @package     Doctrine
 * @subpackage  Search
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @todo Move to separate "Doctrine Search" package.
 */
class Doctrine_Search extends Doctrine_Record_Generator
{
    const INDEX_FILES = 0;

    const INDEX_TABLES = 1;

    protected $_options = array('generateFiles' => false,
                                'type'          => self::INDEX_TABLES,
                                'className'     => '%CLASS%Index',
                                'generatePath'  => false,
                                'table'         => null,
                                'batchUpdates'  => false,
                                'pluginTable'   => false,
                                'fields'        => array(),
                                'connection'    => null,
                                'children'      => array());
    /**
     * __construct 
     * 
     * @param array $options 
     * @return void
     */
    public function __construct(array $options)
    {
        $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
        
        if ( ! isset($this->_options['analyzer'])) {
            $this->_options['analyzer'] = new Doctrine_Search_Analyzer_Standard();
        }
        if ( ! isset($this->_options['connection'])) {
            $this->_options['connection'] = Doctrine_Manager::connection();
        }
    }


    /**
     * search 
     * 
     * @param string $query
     * @return Doctrine_Collection The collection of search results
     */
    public function search($query)
    {
        $q = new Doctrine_Search_Query($this->_table);
        
        $q->query($query);
        
        return $this->_options['connection']->fetchAll($q->getSql(), $q->getParams());;
    }
    
    /**
     * analyze 
     * 
     * @param string $text 
     * @return void
     */
    public function analyze($text)
    {
        return $this->_options['analyzer']->analyze($text);
    }

    /**
     * updateIndex
     * updates the index
     *
     * @param Doctrine_Entity $record
     * @return integer
     */
    public function updateIndex(array $data)
    {
        $this->initialize($this->_options['table']);

        $fields = $this->getOption('fields');
        $class  = $this->getOption('className');
        $name   = $this->getOption('table')->getComponentName();
        $conn   = $this->getOption('table')->getConnection();
        $identifier = $this->_options['table']->getIdentifier();

        $q = Doctrine_Query::create()->delete()
                                     ->from($class);
        foreach ((array) $identifier as $id) {
            $q->addWhere($id . ' = ?', array($data[$id]));
        }
        $q->execute();

        if ($this->_options['batchUpdates'] === true) {
            $index = new $class(); 

            foreach ((array) $this->_options['table']->getIdentifier() as $id) {
                $index->$id = $data[$id];
            }

            $index->save();
        } else {
            foreach ($fields as $field) {

                $value = $data[$field];

                $terms = $this->analyze($value);

                foreach ($terms as $pos => $term) {
                    $index = new $class();

                    $index->keyword = $term;
                    $index->position = $pos;
                    $index->field = $field;
                    foreach ((array) $this->_options['table']->getIdentifier() as $id) {
                        $index->$id = $data[$id];
                    }

                    $index->save();
                }
            }
        }
    }

    /**
     * readTableData 
     * 
     * @param mixed $limit 
     * @param mixed $offset 
     * @return Doctrine_Collection The collection of results
     */
    public function readTableData($limit = null, $offset = null)
    {
        $this->initialize($this->_options['table']);

        $conn      = $this->_options['table']->getConnection();
        $tableName = $this->_options['table']->getTableName();
        $id        = $this->_options['table']->getIdentifier();

        $query = 'SELECT * FROM ' . $conn->quoteIdentifier($tableName)
               . ' WHERE ' . $conn->quoteIdentifier($id)
               . ' IN (SELECT ' . $conn->quoteIdentifier($id)
               . ' FROM ' . $conn->quoteIdentifier($this->_table->getTableName())
               . ' WHERE keyword IS NULL)';

        $query = $conn->modifyLimitQuery($query, $limit, $offset);

        return $conn->fetchAll($query);
    }
    


    /**
     * batchUpdateIndex 
     * 
     * @param mixed $limit 
     * @param mixed $offset 
     * @return void
     */
    public function batchUpdateIndex($limit = null, $offset = null)
    {
        $this->initialize($this->_options['table']);

        $id        = $this->_options['table']->getIdentifier();
        $class     = $this->_options['className'];
        $fields    = $this->_options['fields'];
        $conn      = $this->_options['connection'];
        try {

            $conn->beginTransaction();

            $rows = $this->readTableData($limit, $offset);

            foreach ($rows as $row) {
                $ids[] = $row[$id];
            }

            $conn->exec('DELETE FROM ' 
                        . $conn->quoteIdentifier($this->_table->getTableName())
                        . ' WHERE ' . $conn->quoteIdentifier($id) . ' IN (' . implode(', ', $ids) . ')');
                        
            foreach ($rows as $row) {
                foreach ($fields as $field) {
                    $data  = $row[$field];
        
                    $terms = $this->analyze($data);
        
                    foreach ($terms as $pos => $term) {
                        $index = new $class();
        
                        $index->keyword = $term;
                        $index->position = $pos;
                        $index->field = $field;
                        
                        foreach ((array) $id as $identifier) {
                            $index->$identifier = $row[$identifier];
                        }
    
                        $index->save();
                    }
                }
            }

            $conn->commit();
        } catch (Doctrine_Exception $e) {
            $conn->rollback();
        }
    }

    /**
     * buildDefinition 
     * 
     * @return void
     */
    public function setTableDefinition()
    {
    	if ( ! isset($this->_options['table'])) {
    	    throw new Doctrine_Plugin_Exception("Unknown option 'table'.");
    	}

        $componentName = $this->_options['table']->getComponentName();

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

        $this->hasColumns($columns);
    }
}
