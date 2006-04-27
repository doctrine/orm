<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Collection.class.php");
/**
 * Doctrine_Collection_Batch       a collection of records,
 *                                 with batch load strategy
 *
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Collection_Batch extends Doctrine_Collection {
    /**
     * @var integer $batchSize      batch size
     */
    private $batchSize;
    /**
     * @var array $loaded           an array containing the loaded batches, keys representing the batch indexes
     */
    private $loaded = array();
    
    public function __construct(Doctrine_Table $table) {
        parent::__construct($table);
        $this->batchSize = $this->getTable()->getAttribute(Doctrine::ATTR_BATCH_SIZE);
    }

    /**
     * @param integer $batchSize    batch size
     */
    public function setBatchSize($batchSize) {
        $batchSize = (int) $batchSize;
        if($batchSize <= 0)
            return false;

        $this->batchSize = $batchSize;
        return true;
    }
    /**
     * @return integer
     */
    public function getBatchSize() {
        return $this->batchSize;
    }
    /**
     * load                         load a specified element, by loading the batch the element is part of
     * @param Doctrine_Record $record              data access object
     * @return boolean              whether or not the load operation was successful
     */
    public function load(Doctrine_Record $record) {
        if(empty($this->data))
            return false;

        $id  = $record->getID();
        $identifier = $this->table->getIdentifier();
        foreach($this->data as $key => $v) {
            if(is_object($v)) {
                if($v->getID() == $id)
                    break;

            } elseif(is_array($v[$identifier])) {
                if($v[$identifier] == $id)
                    break;
            }
        }
        $x = floor($key / $this->batchSize);

        if( ! isset($this->loaded[$x])) {

            $e  = $x * $this->batchSize;
            $e2 = ($x + 1)* $this->batchSize;

            $a       = array();
            $proxies = array();

            for($i = $e; $i < $e2 && $i < $this->count(); $i++):
                if(is_object($this->data[$i]))
                    $id = $this->data[$i]->getID();
                elseif(is_array($this->data[$i]))
                    $id = $this->data[$i][$identifier];


                $a[$i] = $id;
            endfor;

            $c = count($a);

            $pk     = $this->table->getPrimaryKeys();
            $query  = $this->table->getQuery()." WHERE ";
            $query .= ($c > 1)?$identifier." IN (":$pk[0]." = ";
            $query .= substr(str_repeat("?, ",count($a)),0,-2);
            $query .= ($c > 1)?") ORDER BY ".$pk[0]." ASC":"";

            $stmt  = $this->table->getSession()->execute($query,array_values($a));

             foreach($a as $k => $id) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if($row === false) 
                    break;

                $this->table->setData($row);
                if(is_object($this->data[$k])) {
                    $this->data[$k]->factoryRefresh($this->table);
                } else {
                    $this->data[$k] = $this->table->getRecord();
                }

            }

            $this->loaded[$x] = true;
            return true;
        } else {
            return false;
        }
    }
    /**
     * get
     * @param mixed $key                the key of the record
     * @return object Doctrine_Record               record
     */
    public function get($key) {
        if(isset($this->data[$key])) {
            switch(gettype($this->data[$key])):
                case "array":
                    // Doctrine_Record didn't exist in cache
                    $this->table->setData($this->data[$key]);
                    $this->data[$key] = $this->table->getProxy();

                    $this->data[$key]->addCollection($this);
                break;
            endswitch;
        } else {

            $this->expand($key);

            if( ! isset($this->data[$key]))
                $this->data[$key] = $this->table->create();

        }


        if(isset($this->reference_field))
            $this->data[$key]->rawSet($this->reference_field,$this->reference);


        return $this->data[$key];
    }
    /**
     * @return Doctrine_Iterator
     */
    public function getIterator() {
        return new Doctrine_Iterator_Expandable($this);
    }
}
?>
