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
    const INDEX_FILES = 0;

    const INDEX_TABLES = 1;

    protected $_options = array('generateFiles' => false,
                                'type'          => self::INDEX_TABLES,
                                'className'     => '%CLASS%Index',
                                'generatePath'  => false,
                                'resource'      => null,
                                'batchUpdates'  => false,
                                'pluginTable'   => false,
                                'fields'        => array(),
                                'connection'    => null);
                                
    protected $_built = false;

    
    public function __construct(array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        
        if ( ! isset($this->_options['analyzer'])) {
            $this->_options['analyzer'] = new Doctrine_Search_Analyzer_Standard();
        }
        if ( ! isset($this->_options['connection'])) {
            $this->_options['connection'] = Doctrine_Manager::connection();
        }
    }


    public function search($query)
    {
        $q = new Doctrine_Search_Query($this->_options['pluginTable']);
        
        $q->query($query);
        
        return $this->_options['connection']->fetchAll($q->getSql(), $q->getParams());;
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
    public function updateIndex(array $data)
    {
        $this->buildDefinition(); 	

        $fields = $this->getOption('fields');
        $class  = $this->getOption('className');
        $name   = $this->getOption('resource')->getComponentName();
        $conn   = $this->getOption('resource')->getConnection();
        $identifier = $this->_options['resource']->getIdentifier();
        
        $q = Doctrine_Query::create()->delete()
                                     ->from($class);
        foreach ((array) $identifier as $id) {
            $q->addWhere($id . ' = ?', array($data[$id]));
        }
        $q->execute();

        if ($this->_options['batchUpdates'] === true) {
            $index = new $class(); 

            foreach ((array) $this->_options['resource']->getIdentifier() as $id) {
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
                    foreach ((array) $this->_options['resource']->getIdentifier() as $id) {
                        $index->$id = $data[$id];
                    }

                    $index->save();
                }
            }
        }
    }

    public function readTableData($limit = null, $offset = null)
    {
        $this->buildDefinition(); 

        $conn      = $this->_options['resource']->getConnection();
        $tableName = $this->_options['resource']->getTableName();
        $id        = $this->_options['resource']->getIdentifier();

        $query = 'SELECT * FROM ' . $conn->quoteIdentifier($tableName)
               . ' WHERE ' . $conn->quoteIdentifier($id)
               . ' IN (SELECT ' . $conn->quoteIdentifier($id)
               . ' FROM ' . $conn->quoteIdentifier($this->_options['pluginTable']->getTableName())
               . ' WHERE keyword IS NULL)';

        $query = $conn->modifyLimitQuery($query, $limit, $offset);

        return $conn->fetchAll($query);
    }
    


    public function batchUpdateIndex($limit = null, $offset = null)
    {
        $this->buildDefinition();

        $id        = $this->_options['resource']->getIdentifier();
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
                        . $conn->quoteIdentifier($this->_options['pluginTable']->getTableName())
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

    public function buildDefinition()
    {
    	if ($this->_built) {
            return true;
    	}
        $this->_built = true;

        $componentName = $this->_options['resource']->getComponentName();

        // check for placeholders
        if (strpos($this->_options['className'], '%') !== false) {
            $this->_options['className'] = str_replace('%CLASS%', $componentName, $this->_options['className']);
        }

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

        $id = $this->_options['resource']->getIdentifier();

        $options = array('className' => $className);

        $fk = $this->generateForeignKeys($this->_options['resource']);
        $columns += $fk;

        $relations = array();
        // only generate relations for database based searches
        if ( ! $this instanceof Doctrine_Search_File) {
            $relations = $this->generateRelation($this->_options['resource'], $fk);
        }

        $this->generateClass($options, $columns, $relations);

        $this->_options['pluginTable'] = $this->_options['connection']->getTable($this->_options['className']);

        return true;
    }
}
