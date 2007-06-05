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
                            'deleteTrigger' => '%TABLE%_ddt',
                            'updateTrigger' => '%TABLE%_dut',
                            'versionTable'  => '%TABLE%_dvt',
                            'identifier'    => '__version',
                            );
                            
    protected $_table;

    public function __construct(Doctrine_Table $table)
    {
    	$this->_table = $table;
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


        $data['columns'] = array_merge(array($this->_options['identifier'] =>
                                array('type' => 'integer',
                                      'primary' => true,
                                      'autoinc' => true)), $data['columns']);
        


        $definition = 'class ' . str_replace('%CLASS%', $this->_table->getComponentName(), $this->_options['className'])
                    . ' extends Doctrine_Record { '
                    . 'public function setTableDefinition() { '
                    . '$this->hasColumns(' . var_export($data['columns'], true) . ');'
                    . '$this->option(\'tableName\', \'' . $data['tableName'] . '\'); } }';
        
        print $definition;

        eval( $definition );
        $data['options']['primary'] = array($this->_options['identifier']);

        $conn->export->createTable($data['tableName'], $data['columns'], $data['options']);
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
              . $conn->quoteIdentifier($this->_table->getTableName()) . '_ddt' . ' DELETE ON '
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
