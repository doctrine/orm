<?php
/**
 * Doctrine_Repository
 * each record is added into Doctrine_Repository at the same time they are created,
 * loaded from the database or retrieved from the cache
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 *
 */
class Doctrine_Repository implements Countable, IteratorAggregate {
    /**
     * @var object Doctrine_Table $table
     */
    private $table;
    /**
     * @var array $registry
     * an array of all records
     * keys representing record object identifiers
     */
    private $registry = array();
    /**
     * constructor
     */
    public function __construct(Doctrine_Table $table) {
        $this->table = $table;
    }
    /** 
     * @return object Doctrine_Table
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * add
     * @param Doctrine_Record $record       record to be added into registry
     */
    public function add(Doctrine_Record $record) {
        $oid = $record->getOID();

        if(isset($this->registry[$oid]))
            return false;

        $this->registry[$oid] = $record;

        return true;
    }
    /**
     * get
     * @param integer $oid
     * @throws InvalidKeyException
     */
    public function get($oid) {
        if( ! isset($this->registry[$oid]))
            throw new InvalidKeyException();

        return $this->registry[$oid];
    }
    /**
     * count
     * Doctrine_Registry implements interface Countable
     * @return integer                      the number of records this registry has
     */
    public function count() {
        return count($this->registry);
    }
    /**
     * @param integer $oid                  object identifier
     * @return boolean                      whether ot not the operation was successful
     */
    public function evict($oid) {
        if( ! isset($this->registry[$oid]))
            return false;
            
        unset($this->registry[$oid]);
        return true;
    }
    /**
     * @return integer                      number of records evicted
     */
    public function evictAll() {
        $evicted = 0;
        foreach($this->registry as $oid=>$record) {
            if($this->evict($oid))
                $evicted++;
        }
        return $evicted;
    }
    /**
     * getIterator
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->registry);
    }
    /**
     * contains
     * @param integer $oid                  object identifier
     */
    public function contains($oid) {
        return isset($this->registry[$oid]);
    }
    /**
     * loadAll
     * @return void
     */
    public function loadAll() {
        $this->table->findAll();
    }
}
?>
