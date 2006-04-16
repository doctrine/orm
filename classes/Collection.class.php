<?php
/**
 * class Doctrine_Collection              a collection of data access objects
 *
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Collection extends Doctrine_Access implements Countable, IteratorAggregate {
    /**
     * @var array $data                     an array containing the data access objects of this collection
     */
    protected $data = array();
    /**
     * @var Doctrine_Table $table           each collection has only records of specified table
     */
    protected $table;
    /**
     * @var Doctrine_Record $reference      collection can belong to a record
     */
    protected $reference;
    /**
     * @var string $reference_field         the reference field of the collection
     */
    protected $reference_field;
    /**
     * @var Doctrine_Relation               the record this collection is related to, if any
     */
    protected $relation;
    /**
     * @var boolean $expanded               whether or not this collection has been expanded
     */
    protected $expanded = false;
    /**
     * @var mixed $generator
     */
    protected $generator;

    /**
     * constructor
     */
    public function __construct(Doctrine_Table $table) {
        $this->table = $table;
        
        $name = $table->getAttribute(Doctrine::ATTR_COLL_KEY);
        if($name !== null) {
            $this->generator = new Doctrine_IndexGenerator($name);
        }
    }
    /**
     * @return object Doctrine_Table
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * @param Doctrine_IndexGenerator $generator
     * @return void
     */
    public function setGenerator($generator) {
        $this->generator = $generator;
    }
    /**
     * @return Doctrine_IndexGenerator
     */
    public function getGenerator() {
        return $this->generator;
    }
    /** 
     * @return array
     */
    public function getData() {
        return $this->data;
    }
    /**
     * @param array $data
     */
    public function addData(array $data) {
        $this->data[] = $data;
    }
    /**
     * @return mixed
     */
    public function getLast() {
        return end($this->data);
    }
    /**
     * @return void
     */
    public function setReference(Doctrine_Record $record,Doctrine_Relation $relation) {
        $this->reference       = $record;
        $this->relation        = $relation;
        
        if($relation instanceof Doctrine_ForeignKey ||
           $relation instanceof Doctrine_LocalKey) {
            $this->reference_field = $relation->getForeign();

            $value = $record->get($relation->getLocal());

            foreach($this as $record) {
                if($value !== null) {
                    $record->set($this->reference_field, $value);
                } else {
                    $record->set($this->reference_field, $this->reference);
                }
            }
        }
    }
    /**
     * @return mixed
     */
    public function getReference() {
        return $this->reference;
    }
    /**
     * @return boolean
     */
    public function expand($i = null) {
        if( ! isset($this->reference))
            return false;

        $id = $this->reference->getID();

        if(empty($id))
            return false;

        foreach($this->data as $v) {
            switch(gettype($v)):
                case "array":
                    $ids[] = $v['id'];
                break;
                case "object":
                    $id = $v->getID();
                    if( ! empty($id))
                        $ids[] = $id;
                break;
            endswitch;
        }

        if($this instanceof Doctrine_Collection_Immediate) {
            $fields = implode(", ",$this->table->getColumnNames());
        } else {
            $fields = implode(", ",$this->table->getPrimaryKeys());
        }

        if($this->relation instanceof Doctrine_ForeignKey) {
            $str = "";
            $params = array($this->reference->getID());

            if( ! empty($ids)) {
                $str = " && id NOT IN (".substr(str_repeat("?, ",count($ids)),0,-2).")";
                $params = array_merge($params,$ids);
            }
            $str = " WHERE ".$this->reference_field." = ?".$str;
            $query = "SELECT ".$fields." FROM ".$this->table->getTableName().$str;
            $coll  = $this->table->execute($query,$params);

        } elseif($this->relation instanceof Doctrine_Association) {

            $asf     = $fk->getAssociationFactory();
            $query   = "SELECT ".$foreign." FROM ".$asf->getTableName()." WHERE ".$local."=".$this->getID();

            $table = $fk->getTable();
            $graph   = new Doctrine_DQL_Parser($table->getSession());

            $q       = "FROM ".$table->getComponentName()." WHERE ".$table->getComponentName().".id IN ($query)";
        }

        foreach($coll as $record) {
            if(isset($this->reference_field))
                $record->rawSet($this->reference_field, $this->reference);
            
            $this->reference->addReference($record);
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function remove($key) {
        if( ! isset($this->data[$key]))
            throw new InvalidKeyException();

        $removed = $this->data[$key];
        
        unset($this->data[$key]);
        return $removed;
    }
    /**
     * @param mixed $key
     * @return boolean
     */
    public function contains($key) {
        return isset($this->data[$key]);
    }
    /**
     * @param mixed $key
     * @return object Doctrine_Record           return a specified dao
     */
    public function get($key) {
        if( ! isset($this->data[$key])) {
            $this->expand();

            if( ! isset($this->data[$key]))
                $this->data[$key] = $this->table->create();

            if(isset($this->reference_field)) {
                $value = $this->reference->get($this->relation->getLocal());

                if($value !== null) {
                    $this->data[$key]->set($this->reference_field, $value);
                } else {
                    $this->data[$key]->set($this->reference_field, $this->reference);
                }
            }
        }

        return $this->data[$key];
    }

    /**
     * @return array                an array containing all primary keys
     */
    public function getPrimaryKeys() {
        $list = array();
        foreach($this->data[$key] as $record):
            $list[] = $record->getID();
        endforeach;
        return $list;
    }
    /**
     * returns all keys
     * @return array
     */
    public function getKeys() {
        return array_keys($this->data);
    }
    /**
     * count
     * this class implements interface countable
     * @return integer                              number of records in this collection
     */
    public function count() {
        return count($this->data);
    }
    /**
     * set 
     * @param integer $key
     * @param Doctrine_Record $record
     * @return void
     */
    public function set($key,Doctrine_Record $record) {
        if(isset($this->reference_field))
            $record->set($this->reference_field,$this->reference);

        $this->data[$key] = $record;
    }

    /**
     * adds a record to collection
     * @param Doctrine_Record $record              record to be added
     * @param string $key                          optional key for the record
     * @return boolean
     */
    public function add(Doctrine_Record $record,$key = null) {
        if(isset($this->reference_field))
            $record->rawSet($this->reference_field,$this->reference);

        if(isset($key)) {
            if(isset($this->data[$key]))
                return false;

            $this->data[$key] = $record;
        }

        if(isset($this->generator)) {
            $key = $this->generator->getIndex($record);
            $this->data[$key] = $record;
        } else
            $this->data[] = $record;
        return true;
    }
    /**
     * save
     * saves all records
     */
    public function save() {
        $this->table->getSession()->saveCollection($this);
    }
    /**
     * single shot delete
     * deletes all records from this collection
     * uses only one database query to perform this operation
     * @return boolean
     */
    public function delete() {
        $ids = $this->table->getSession()->deleteCollection($this);
        $this->data = array();
    }
    /**
     * getIterator
     * @return object ArrayIterator
     */
    public function getIterator() {
        $data = $this->data;
        return new ArrayIterator($data);
    }
    /**
     * returns a string representation of this object
     */
    public function __toString() {
        return Doctrine_Lib::getCollectionAsString($this);
    }
}
?>
