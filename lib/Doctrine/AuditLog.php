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
 * Doctrine_AuditLog
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_AuditLog
{
    protected $_options = array(
                            'className'     => '%CLASS%Version',
                            'deleteTrigger' => '%TABLE%_ddtr',
                            'updateTrigger' => '%TABLE%_dutr',
                            'versionTable'  => '%TABLE%_dvt',
                            'versionColumn'    => 'version',
                            );
                            
    protected $_table;
    
    protected $_auditTable;

    public function __construct(Doctrine_Table $table)
    {
    	$this->_table = $table;

    }
    /**
     * __get
     * an alias for getOption
     *
     * @param string $option
     */
    public function __get($option)
    {
        if (isset($this->options[$option])) {
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
     * getOptions
     * returns all options of this table and the associated values
     *
     * @return array    all options and their values
     */
    public function getOptions()
    {
        return $this->_options;
    }
    /**
     * setOption
     * sets an option and returns this object in order to
     * allow flexible method chaining
     *
     * @see slef::$_options             for available options
     * @param string $name              the name of the option to set
     * @param mixed $value              the value of the option
     * @return Doctrine_AuditLog        this object
     */
    public function setOption($name, $value)
    {
        if ( ! isset($this->_options[$name])) {
            throw new Doctrine_Exception('Unknown option ' . $name);
        }
        $this->_options[$name] = $value;
    }
    /**
     * getOption
     * returns the value of given option
     *
     * @param string $name  the name of the option
     * @return mixed        the value of given option
     */
    public function getOption($name)
    {
        if (isset($this->_options[$name])) {
            return $this->_options[$name];
        }
        return null;
    }
    public function audit()
    {
        $conn = $this->_table->getConnection();

        // begin new transaction
        $conn->beginTransaction();
        try {

            // create the version table and the triggers
            $this->createVersionTable();
            $conn->execute($this->deleteTriggerSql());
            $conn->execute($this->updateTriggerSql());

            // commit structure changes
            $conn->commit();
        } catch(Doctrine_Connection_Exception $e) {
            $conn->rollback();
        }
    }
    public function getVersion(Doctrine_Record $record, $version)
    {
        $className = str_replace('%CLASS%', $this->_table->getComponentName(), $this->_options['className']);
        
        $q = new Doctrine_Query();
        
        $values = array();
        foreach ((array) $this->_table->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }
        $where = implode(' AND ', $conditions) . ' AND ' . $className . '.' . $this->_options['versionColumn'] . ' = ?';
        
        $values[] = $version;

        return $q->from($className)
                 ->where($where)
                 ->execute($values, Doctrine::FETCH_ARRAY);
    }
    public function createVersionTable()
    {
        $data = $this->_table->getExportableFormat(false);
        $conn = $this->_table->getConnection();
        $data['tableName'] = str_replace('%TABLE%', $data['tableName'], $this->_options['versionTable']);

        foreach ($data['columns'] as $name => $def) {
            unset($data['columns'][$name]['autoinc']);
            unset($data['columns'][$name]['autoincrement']);
            unset($data['columns'][$name]['sequence']);
            unset($data['columns'][$name]['seq']);
        }
        $data['columns'][$this->_options['versionColumn']]['primary'] = true;

        $className  =  str_replace('%CLASS%', $this->_table->getComponentName(), $this->_options['className']);
        $definition = 'class ' . $className
                    . ' extends Doctrine_Record { '
                    . 'public function setTableDefinition() { '
                    . '$this->hasColumns(' . var_export($data['columns'], true) . ');'
                    . '$this->option(\'tableName\', \'' . $data['tableName'] . '\'); } }';

        $this->_table->getRelationParser()->bind($className, array(
                                  'local'   => $this->_table->getIdentifier(),
                                  'foreign' => $this->_table->getIdentifier(),
                                  'type'    => Doctrine_Relation::MANY));


        $this->_table->addListener(new Doctrine_AuditLog_Listener($this));

        eval($definition);

        $data['options']['primary'][] = $this->_options['versionColumn'];


    	$this->_auditTable = $this->_table->getConnection()->getTable($className);
    }
    /**
     * deleteTriggerSql
     *
     * returns the sql needed for the delete trigger creation
     */
    public function deleteTriggerSql()
    {
    	$conn = $this->_table->getConnection();
    	$columnNames = $this->_table->getColumnNames();
    	$oldColumns  = array_map(array($this, 'formatOld'), $columnNames);
        $sql  = 'CREATE TRIGGER '
              . $conn->quoteIdentifier($this->_table->getTableName()) . '_ddt' . ' BEFORE DELETE ON '
              . $conn->quoteIdentifier($this->_table->getTableName())
              . ' BEGIN'
              . ' INSERT INTO ' . $this->_table->getTableName() . '_dvt ('
              . implode(', ', array_map(array($conn, 'quoteIdentifier'), $columnNames))
              . ') VALUES ('
              . implode(', ', array_map(array($conn, 'quoteIdentifier'), $oldColumns))
              . ');'
              . ' END;';
        return $sql;
    }
    /**
     * updateTriggerSql
     *
     * returns the sql needed for the update trigger creation
     */
    public function updateTriggerSql()
    {
    	$conn = $this->_table->getConnection();
    	$columnNames = $this->_table->getColumnNames();
    	$oldColumns  = array_map(array($this, 'formatOld'), $columnNames);
        $sql  = 'CREATE TRIGGER '
              . $conn->quoteIdentifier($this->_table->getTableName()) . '_dut' . ' UPDATE ON '
              . $conn->quoteIdentifier($this->_table->getTableName())
              . ' BEGIN'
              . ' INSERT INTO ' . $this->_table->getTableName() . '_dvt ('
              . implode(', ', array_map(array($conn, 'quoteIdentifier'), $columnNames))
              . ') VALUES ('
              . implode(', ', array_map(array($conn, 'quoteIdentifier'), $oldColumns))
              . ');'
              . ' END;';
        return $sql;
    }
    public function formatOld($column)
    {
        return 'old.' . $column;
    }
}
