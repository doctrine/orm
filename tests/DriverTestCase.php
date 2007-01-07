<?php
class AdapterMock implements Doctrine_Adapter_Interface {
    private $name;
    
    private $queries = array();
    
    private $exception = array();

    public function __construct($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }
    public function pop() {
        return array_pop($this->queries);
    }
    public function forceException($name, $message = '', $code = 0) {
        $this->exception = array($name, $message, $code);
    }
    public function prepare($prepareString){ 
        return new AdapterStatementMock;
    }
    public function query($queryString) {
        $this->queries[] = $queryString;

        $e    = $this->exception;

        if( ! empty($e)) {
            $name = $e[0];

            $this->exception = array();

            throw new $name($e[1], $e[2]);
        }

        return new AdapterStatementMock;
    }
    public function getAll() {
        return $this->queries;
    }
    public function quote($input) {
        return "'" . addslashes($input) . "'";
    }
    public function exec($statement) { 
        $this->queries[] = $statement;

        $e    = $this->exception;

        if( ! empty($e)) {
            $name = $e[0];

            $this->exception = array();

            throw new $name($e[1], $e[2]);
        }

        return 0;
    }
    public function lastInsertId()
    {
    	$this->queries[] = 'LAST_INSERT_ID()';
        return 1;
    }
    public function beginTransaction(){ 
        $this->queries[] = 'BEGIN TRANSACTION';
    }
    public function commit(){ 
        $this->queries[] = 'COMMIT';
    }
    public function rollBack(){ }
    public function errorCode(){ }
    public function errorInfo(){ }
    public function getAttribute($attribute) {
        if($attribute == PDO::ATTR_DRIVER_NAME)
            return strtolower($this->name);
    }
    public function setAttribute($attribute, $value) {
                                   	
    }
}
class AdapterStatementMock {
    public function fetch($fetchMode) { 
        return array();
    }
    public function fetchAll($fetchMode) {
        return array();
    }
    public function execute() {
        return true;
    }
    public function fetchColumn($colnum) {
        return 0;
    }
}

class Doctrine_Driver_UnitTestCase extends UnitTestCase {
    protected $driverName = false;
    protected $generic = false;
    protected $manager;
    protected $conn;
    protected $adapter;
    protected $export;
    protected $dataDict;
    protected $transaction;

    public function __construct($driverName, $generic = false) {

        $this->driverName = $driverName;
        $this->generic    = $generic;
    }
    public function assertDeclarationType($type, $type2) {
        $dec = $this->getDeclaration($type);
        if( ! is_array($type2))
            $type2 = array($type2);
        $this->assertEqual($dec[0], $type2);
    }
    public function getDeclaration($type) {
        return $this->dataDict->getPortableDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 1, 'fixed' => true));
    }
    public function setDriverName($driverName) {
        $this->driverName = $driverName;
    }
    public function init() {
        $this->adapter = new AdapterMock($this->driverName);
        $this->manager = Doctrine_Manager::getInstance();
        $this->manager->setDefaultAttributes();
        $this->conn = $this->manager->openConnection($this->adapter);

        if( ! $this->generic) {
            $this->export   = $this->conn->export;

            $name = $this->adapter->getName();

            if($this->adapter->getName() == 'oci')
                $name = 'Oracle';
            
            $tx = 'Doctrine_Transaction_' . ucwords($name);
            $dataDict = 'Doctrine_DataDict_' . ucwords($name);
            
            $exc  = 'Doctrine_Connection_' . ucwords($name) . '_Exception';
            
            $this->exc = new $exc();
            if(class_exists($tx))
                $this->transaction = new $tx($this->conn);
            if(class_exists($dataDict)) {
                $this->dataDict = new $dataDict($this->conn);
            }
            //$this->dataDict = $this->conn->dataDict;
        } else {
            $this->export   = new Doctrine_Export($this->conn);
            $this->transaction = new Doctrine_Transaction($this->conn);
        }
    }

    public function setUp() {
        static $init = false;
        if( ! $init) {
            $this->init();
            $init = true;
        }
    }
}
?>
