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
 * Doctrine_Connection
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 */
abstract class Doctrine_Connection extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * @var $dbh                                the database handler
     */
    private $dbh;
    /**
     * @var Doctrine_Transaction $transaction   the transaction object
     */
    private $transaction;
    /**
     * @var Doctrine_UnitOfWork $unitOfWork     the unit of work object
     */
    private $unitOfWork;
    /**
     * @var array $tables                       an array containing all the initialized Doctrine_Table objects
     *                                          keys representing Doctrine_Table component names and values as Doctrine_Table objects
     */
    protected $tables           = array();
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName;
    /**
     * @var array $supported                    an array containing all features this driver supports, 
     *                                          keys representing feature names and values as 
     *                                          one of the following (true, false, 'emulated')
     */
    protected $supported        = array();
    /**
     * @var Doctrine_DataDict $dataDict
     */
    private $dataDict;
    
    protected $options = array();

    private $modules = array('Transaction' => false,
                             'Expression'  => false,
                             'DataDict'    => false,
                             'Export'      => false,
                             'UnitOfWork'  => false,
                             );
    /**
     * @var array $availibleDrivers         an array containing all availible drivers
     */
    private static $availibleDrivers    = array(
                                        'Mysql',
                                        'Pgsql',
                                        'Oracle',
                                        'Informix',
                                        'Mssql',
                                        'Sqlite',
                                        'Firebird'
                                        );

    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager                 the manager object
     * @param PDO|Doctrine_Adapter_Interface $adapter   database driver
     */
    public function __construct(Doctrine_Manager $manager, $adapter) {
        if( ! ($adapter instanceof PDO) && ! in_array('Doctrine_Adapter_Interface', class_implements($adapter)))
            throw new Doctrine_Connection_Exception("First argument should be an instance of PDO or implement Doctrine_Adapter_Interface");

        $this->dbh   = $adapter;

        $this->transaction  = new Doctrine_Connection_Transaction($this);
        $this->unitOfWork   = new Doctrine_Connection_UnitOfWork($this);

        $this->setParent($manager);

        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
    }
    /**
     * getName
     * returns the name of this driver
     *
     * @return string           the name of this driver
     */
    public function getName() {
        return $this->driverName;
    }
    /**
     * __get
     * lazy loads given module and returns it
     *
     * @param string $name                      the name of the module to get
     * @throws Doctrine_Connection_Exception    if trying to get an unknown module
     * @return Doctrine_Connection_Module       connection module
     */
    public function __get($name) {
        if( ! isset($this->modules[$name]))
            throw new Doctrine_Connection_Exception('Unknown module ' . $name);
           
        if($this->modules[$name] === false) {
            switch($name) {
                case 'UnitOfWork':
                    $this->modules[$name] = new Doctrine_Connection_UnitOfWork($this);
                break;
                default:
                    $class = 'Doctrine_' . $name . '_' . $this->getName();
                    $this->modules[$name] = new $class($this);
            }
        }

        return $this->modules[$name];
    }
    /**
     * Quotes pattern (% and _) characters in a string)
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @param   string  the input string to quote
     *
     * @return  string  quoted string
     */
    public function escapePattern($text) {
        if ($this->string_quoting['escape_pattern']) {
            $text = str_replace($this->string_quoting['escape_pattern'], $this->string_quoting['escape_pattern'] . $this->string_quoting['escape_pattern'], $text);
            foreach ($this->wildcards as $wildcard) {
                $text = str_replace($wildcard, $this->string_quoting['escape_pattern'] . $wildcard, $text);
            }
        }
        return $text;
    }
    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str           identifier name to be quoted
     * @param bool $checkOption     check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str, $checkOption = true) {
        if ($checkOption && ! $this->options['quote_identifier']) {
            return $str;
        }
        $str = str_replace($this->identifier_quoting['end'], $this->identifier_quoting['escape'] . $this->identifier_quoting['end'], $str);
        return $this->identifier_quoting['start'] . $str . $this->identifier_quoting['end'];
    }
    /**
     * getUnitOfWork
     *
     * returns the unit of work object
     *
     * @return Doctrine_UnitOfWork
     */
    public function getUnitOfWork() {
        return $this->unitOfWork;
    }
    /**
     * getTransaction
     *
     * returns the current transaction object
     *
     * @return Doctrine_Transaction
     */
    public function getTransaction() {
        return $this->transaction;
    }
    /**
     * returns the manager that created this connection
     *
     * @return Doctrine_Manager
     */
    public function getManager() {
        return $this->getParent();
    }
    /**
     * returns the database handler of which this connection uses
     *
     * @return PDO              the database handler
     */
    public function getDbh() {
        return $this->dbh;
    }
    /**
     * converts given driver name
     *
     * @param
     */
    public function driverName($name) {
    }
    /**
     * supports
     *
     * @param string $feature   the name of the feature
     * @return boolean          whether or not this drivers supports given feature
     */
    public function supports($feature) {
        return (isset($this->supported[$feature]) &&
                $this->supported[$feature] === 'emulated' || 
                $this->supported[$feature]);
    }
    /**
     * Removes any formatting in an sequence name using the 'seqname_format' option
     *
     * @param string $sqn string that containts name of a potential sequence
     * @return string name of the sequence with possible formatting removed
     */
    public function fixSequenceName($sqn) {
        $seq_pattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $db->options['seqname_format']).'$/i';
        $seq_name = preg_replace($seq_pattern, '\\1', $sqn);
        if ($seq_name && ! strcasecmp($sqn, $db->getSequenceName($seq_name))) {
            return $seq_name;
        }
        return $sqn;
    }
    /**
     * Removes any formatting in an index name using the 'idxname_format' option
     *
     * @param string $idx string that containts name of anl index
     * @return string name of the index with possible formatting removed
     */
    public function fixIndexName($idx) {
        $idx_pattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $db->options['idxname_format']).'$/i';
        $idx_name = preg_replace($idx_pattern, '\\1', $idx);
        if ($idx_name && !strcasecmp($idx, $db->getIndexName($idx_name))) {
            return $idx_name;
        }
        return $idx;
    }
    /**
     * returns a datadict object
     *
     * @return Doctrine_DataDict
     */
    public function getDataDict() {
        if(isset($this->dataDict))
            return $this->dataDict;

        $class = 'Doctrine_DataDict_' . $this->getName();
        $this->dataDict = new $class($this->dbh);

        return $this->dataDict;
    }
    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param                   string  name of the table on which the REPLACE query will
     *                          be executed.
     *
     * @param   array           an associative array that describes the fields and the
     *                          values that will be inserted or updated in the specified table. The
     *                          indexes of the array are the names of all the fields of the table.
     *
     *                          The values of the array are values to be assigned to the specified field.
     *
     * @param array $keys       an array containing all key fields (primary key fields
     *                          or unique index fields) for this table
     *
     *                          the uniqueness of a row will be determined according to
     *                          the provided key fields
     *
     *                          this method will fail if no key fields are specified
     *
     * @throws Doctrine_Connection_Exception        if this driver doesn't support replace
     * @throws Doctrine_Connection_Exception        if some of the key values was null
     * @throws Doctrine_Connection_Exception        if there were no key fields
     * @throws PDOException                         if something fails at PDO level
     * @return void
     */
    public function replace($table, array $fields, array $keys) {
        if( ! $this->supports('replace'))
            throw new Doctrine_Connection_Exception('replace query is not supported');


        if(empty($keys))
            throw new Doctrine_Connection_Exception('Not specified which fields are keys');

        $condition = $values = array();

        foreach($fields as $name => $value) {
            $values[$name] = $value;

            if(in_array($name, $keys)) {
                if($value === null)
                    throw new Doctrine_Connection_Exception('key value '.$name.' may not be null');

                $condition[]       = $name . ' = ?';
                $conditionValues[] = $value;
            }
        }

        $query          = 'DELETE FROM '. $table . ' WHERE ' . implode(' AND ', $condition);
        $affectedRows   = $this->dbh->exec($query);

        $this->insert($table, $values);

        $affectedRows++;


        return $affectedRows;
    }
    /**
     * Inserts a table row with specified data.
     *
     * @param string $table     The table to insert data into.
     * @param array $values     An associateve array containing column-value pairs.
     * @return boolean
     */
    public function insert($table, array $values = array()) {
        if(empty($values))
            return false;

        // column names are specified as array keys
        $cols = array_keys($values);

        // build the statement
        $query = "INSERT INTO $table "
               . '(' . implode(', ', $cols) . ') '
               . 'VALUES (' . substr(str_repeat('?, ', count($values)), 0, -2) . ')';

        // prepare and execute the statement
        $stmt   = $this->dbh->prepare($query);
        $stmt->execute(array_values($values));

        return true;
    }
    /**
     * returns the next value in the given sequence
     *
     * @param string $sequence
     * @throws PDOException     if something went wrong at database level
     * @return integer
     */
    public function nextId($sequence) {
        throw new Doctrine_Connection_Exception('NextId() for sequences not supported by this driver.');
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     * @param resource  connection handle
     *
     * @throws Doctrine_Connection_Exception            if the feature is not supported by the driver
     * @return true on success, MDB2 Error Object on failure
     */
    public function setCharset($charset) {
        throw new Doctrine_Connection_Exception('Altering charset not supported by this driver.');
    }
    /**
     * fetchAll
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchAll($statement, array $params = array()) {
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * fetchOne
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return mixed
     */
    public function fetchOne($statement, array $params = array()) {
        return current($this->query($statement, $params)->fetch(PDO::FETCH_NUM));
    }
    /**
     * fetchRow
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchRow($statement, array $params = array()) {
        return $this->query($statement, $params)->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * fetchArray
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchArray($statement, array $params = array()) {
        return $this->query($statement, $params)->fetch(PDO::FETCH_NUM);
    }
    /**
     * fetchColumn
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchColumn($statement, array $params = array()) {
        $result = $this->query($statement, $params)->fetchAll(PDO::FETCH_COLUMN);

        if($this->options['portability'] & Doctrine::PORTABILITY_FIX_CASE)
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);

        return $result;
    }
    /**
     * fetchAssoc
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchAssoc($statement, array $params = array()) {
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * fetchBoth
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchBoth($statement, array $params = array()) { 
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_BOTH);
    }


    /**
     * query
     * queries the database using Doctrine Query Language
     * returns a collection of Doctrine_Record objects
     *
     * <code>
     * $users = $conn->query('SELECT u.* FROM User u');
     *
     * $users = $conn->query('SELECT u.* FROM User u WHERE u.name LIKE ?', array('someone'));
     * </code>
     *
     * @param string $query             DQL query
     * @param array $params             query parameters
     * @see Doctrine_Query
     * @return Doctrine_Collection      Collection of Doctrine_Record objects
     */
    public function query($query, array $params = array()) {
        $parser = new Doctrine_Query($this);

        return $parser->query($query, $params);
    }
    /**
     * query
     * queries the database using Doctrine Query Language and returns 
     * the first record found
     *
     * <code>
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.id = ?', array(1));
     *
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.name LIKE ? AND u.password = ?', 
     *         array('someone', 'password')
     *         );
     * </code>
     *
     * @param string $query             DQL query
     * @param array $params             query parameters
     * @see Doctrine_Query
     * @return Doctrine_Record|false    Doctrine_Record object on success,
     *                                  boolean false on failure
     */
    public function queryOne($query, array $params = array()) {
        $parser = new Doctrine_Query($this);

        $coll = $parser->query($query, $params);
        if( ! $coll->contains(0))
            return false;
        
        return $coll[0];
    }
    /**
     * queries the database with limit and offset
     * added to the query and returns a PDOStatement object
     *
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @return PDOStatement
     */
    public function select($query,$limit = 0,$offset = 0) {
        if($limit > 0 || $offset > 0)
            $query = $this->modifyLimitQuery($query, $limit, $offset);

        return $this->dbh->query($query);
    }
    /**
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement
     */
    public function execute($query, array $params = array()) {
        if( ! empty($params)) {
            $stmt = $this->dbh->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } else {
            return $this->dbh->query($query);
        }
    }
    /**
     * hasTable
     * whether or not this connection has table $name initialized
     *
     * @param mixed $name
     * @return boolean
     */
    public function hasTable($name) {
        return isset($this->tables[$name]);
    }
    /**
     * returns a table object for given component name
     *
     * @param string $name              component name
     * @return object Doctrine_Table
     */
    public function getTable($name) {
        if(isset($this->tables[$name]))
            return $this->tables[$name];

        $class = $name."Table";

        if(class_exists($class) && in_array("Doctrine_Table", class_parents($class))) {
            return new $class($name, $this);
        } else {

            return new Doctrine_Table($name, $this);
        }
    }
    /**
     * returns an array of all initialized tables
     *
     * @return array
     */
    public function getTables() {
        return $this->tables;
    }
    /**
     * returns an iterator that iterators through all 
     * initialized table objects
     *
     * <code>
     * foreach($conn as $index => $table) {
     *      print $table;  // get a string representation of each table object
     * }
     * </code>
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->tables);
    }
    /**
     * returns the count of initialized table objects
     *
     * @return integer
     */
    public function count() {
        return count($this->tables);
    }
    /**
     * addTable
     * adds a Doctrine_Table object into connection registry
     *
     * @param $objTable             a Doctrine_Table object to be added into registry
     * @return boolean
     */
    public function addTable(Doctrine_Table $objTable) {
        $name = $objTable->getComponentName();

        if(isset($this->tables[$name]))
            return false;

        $this->tables[$name] = $objTable;
        return true;
    }
    /**
     * create
     * creates a record
     *
     * create                       creates a record
     * @param string $name          component name
     * @return Doctrine_Record      Doctrine_Record object
     */
    public function create($name) {
        return $this->getTable($name)->create();
    }
    /**
     * flush                        
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    public function flush() {
        $this->beginTransaction();
        $this->unitOfWork->saveAll();
        $this->commit();
    }
    /**
     * clear
     * clears all repositories
     *
     * @return void
     */
    public function clear() {
        foreach($this->tables as $k => $table) {
            $table->getRepository()->evictAll();
            $table->clear();
        }
    }
    /**
     * evictTables
     * evicts all tables
     *
     * @return void
     */
    public function evictTables() {
        $this->tables = array();
    }
    /**
     * close
     * closes the connection
     *
     * @return void
     */
    public function close() {
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreClose($this);

        $this->clear();

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onClose($this);
    }
    /**
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel() {
        return $this->transaction->getTransactionLevel();
    }
    /**
     * beginTransaction
     * starts a new transaction
     *
     * this method can be listened by onPreBeginTransaction and onBeginTransaction
     * listener methods
     *
     * @return void
     */
    public function beginTransaction() {
        $this->transaction->beginTransaction();
    }
    /**
     * commits the current transaction
     * if lockmode is optimistic this method starts a transaction
     * and commits it instantly
     *
     * @return void
     */
    public function commit() {
        $this->transaction->commit();
    }
    /**
     * rollback
     * rolls back all transactions
     *
     * this method also listens to onPreTransactionRollback and onTransactionRollback
     * eventlisteners
     *
     * @return void
     */
    public function rollback() {
        $this->transaction->rollback();
    }
    /**
     * saves the given record
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function save(Doctrine_Record $record) {
        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);


        switch($record->getState()):
            case Doctrine_Record::STATE_TDIRTY:
                $this->transaction->insert($record);
            break;
            case Doctrine_Record::STATE_DIRTY:
            case Doctrine_Record::STATE_PROXY:
                $this->transaction->update($record);
            break;
            case Doctrine_Record::STATE_CLEAN:
            case Doctrine_Record::STATE_TCLEAN:
                // do nothing
            break;
        endswitch;

        $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
    }
    /**
     * deletes this data access object and all the related composites
     * this operation is isolated by a transaction
     * 
     * this event can be listened by the onPreDelete and onDelete listeners
     *
     * @return boolean      true on success, false on failure
     */
    public function delete(Doctrine_Record $record) {
        if( ! $record->exists())
            return false;

        $this->beginTransaction();

        $record->getTable()->getListener()->onPreDelete($record);

        $this->unitOfWork->deleteComposites($record);

        $this->transaction->addDelete($record);

        $record->getTable()->getListener()->onDelete($record);

        $this->commit();

        return true;
    }
    /**
     * returns a string representation of this object
     * @return string
     */
    public function __toString() {
        return Doctrine_Lib::getConnectionAsString($this);
    }
}

