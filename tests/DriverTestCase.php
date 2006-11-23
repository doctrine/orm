<?php
class AdapterMock implements Doctrine_Adapter_Interface {
    private $name;
    
    private $queries = array();
    

    public function __construct($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }
    public function pop() {
        return array_pop($this->queries);
    }

    public function prepare($prepareString){ 
        return new AdapterStatementMock;
    }
    public function query($queryString) {
        $this->queries[] = $queryString;
        
        return new AdapterStatementMock;
    }
    public function quote($input){ }
    public function exec($statement) { 
        $this->queries[] = $statement;
        
        return 0;
    }
    public function lastInsertId(){ }
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
            return $this->name;
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
    public function init() {
        $this->adapter = new AdapterMock($this->driverName);
        $this->manager = Doctrine_Manager::getInstance();
        $this->manager->setDefaultAttributes();
        $this->conn = $this->manager->openConnection($this->adapter);
        if( ! $this->generic) {
            $this->export   = $this->conn->export;

            if($this->adapter->getName() == 'oci')
                $tx = 'Doctrine_Transaction_Oracle'; 
            else
                $tx = 'Doctrine_Transaction_' . ucwords($this->adapter->getName());
            if(class_exists($tx))
            $this->transaction = new $tx($this->conn);
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
