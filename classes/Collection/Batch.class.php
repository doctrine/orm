<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Collection.class.php");
/**
 * Doctrine_Collection_Batch       a collection of data access objects,
 *                          with batch load strategy
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
    
    public function __construct(Doctrine_DQL_Parser $graph,$key) {
        parent::__construct($graph->getTable($key));
        $this->data      = $graph->getData($key);
        if( ! is_array($this->data)) 
            $this->data = array();

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
        foreach($this->data as $key => $v) {
            if(is_object($v)) {
                if($v->getID() == $id)
                    break;

            } elseif(is_array($v["id"])) {
                if($v["id"] == $id)
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
                    $id = $this->data[$i]["id"];

                $load = false;

                // check the cache
                // no need of fetching the same data twice
                try {
                    $record = $this->table->getCache()->fetch($id);
                } catch(InvalidKeyException $ex) {
                    $load = true;
                }

                if($load)
                    $a[] = $id;
            endfor;

            $c = count($a);

            $query  = $this->table->getQuery()." WHERE ";
            $query .= ($c > 1)?"id IN (":"id = ";
            $query .= substr(str_repeat("?, ",count($a)),0,-2);
            $query .= ($c > 1)?")":"";

            $stmt  = $this->table->getSession()->execute($query,$a);

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $this->table->setData($row);

                if(is_object($this->data[$e])) {
                    $this->data[$e]->factoryRefresh($this->table);
                } else {
                    $this->data[$e] = $this->table->getRecord();
                }

                $e++;
            endwhile;
            $this->loaded[$x] = true;
            return true;
        } else {
            return false;
        }
    }
    /**
     * get
     * @param mixed $key                the key of the data access object
     * @return object Doctrine_Record               data access object
     */
    public function get($key) {
        if(isset($this->data[$key])) {
            switch(gettype($this->data[$key])):
                case "array":
                    try {

                        // try to fetch the Doctrine_Record from cache
                        if( ! isset($this->data[$key]["id"]))
                            throw new InvalidKeyException();
                            
                        $record = $this->table->getCache()->fetch($this->data[$key]["id"]);

                    } catch(InvalidKeyException $e) {

                        // Doctrine_Record didn't exist in cache
                        $this->table->setData($this->data[$key]);
                        $proxy = $this->table->getProxy();
                        $record = $proxy;
                    }

                    $record->addCollection($this);
                break;
                case "object":
                    $record = $this->data[$key];
                break;
            endswitch;
        } else {

            $this->expand();

            if(isset($this->data[$key])) {
                $record = $this->data[$key];
            } else {
                $record = $this->table->create();
            }
        }


        if(isset($this->reference_field))
            $record->set($this->reference_field,$this->reference);

        $this->data[$key] = $record;
        return $this->data[$key];
    }
    /**
     * @return Doctrine_BatchIterator
     */
    public function getIterator() {
        return new Doctrine_BatchIterator($this);
    }
}
?>
