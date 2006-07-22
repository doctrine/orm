<?php
require_once("Access.php");
/**
 * Doctrine_Collection
 * Collection of Doctrine_Record objects.
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Collection extends Doctrine_Access implements Countable, IteratorAggregate, Serializable {
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
     * @var boolean $expandable             whether or not this collection has been expanded
     */
    protected $expandable = true;
    /**
     * @var array $expanded
     */
    protected $expanded = array();
    /**
     * @var mixed $generator
     */
    protected $generator;
    /**
     * @var Doctrine_Null $null             used for extremely fast SQL null value testing
     */
    protected static $null;

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
     * initNullObject
     */
    public static function initNullObject(Doctrine_Null $null) {
        self::$null = $null;
    }
    /**
     * @return object Doctrine_Table
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * this method is automatically called when this Doctrine_Collection is serialized
     *
     * @return array
     */
    public function serialize() {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['reference_field']);
        unset($vars['relation']);
        unset($vars['expandable']);
        unset($vars['expanded']);
        unset($vars['generator']);

        $vars['table'] = $vars['table']->getComponentName();

        return serialize($vars);
    }
    /**
     * unseralize
     * this method is automatically called everytime a Doctrine_Collection object is unserialized
     *
     * @return void
     */
    public function unserialize($serialized) {
        $manager    = Doctrine_Manager::getInstance();
        $session    = $manager->getCurrentSession();

        $array = unserialize($serialized);

        foreach($array as $name => $values) {
            $this->$name = $values;
        }

        $this->table        = $session->getTable($this->table);

        $this->expanded     = array();
        $this->expandable   = true;

        $name = $this->table->getAttribute(Doctrine::ATTR_COLL_KEY);
        if($name !== null) {
            $this->generator = new Doctrine_IndexGenerator($name);
        }
    }
    /**
     * whether or not an offset batch has been expanded
     * @return boolean
     */
    public function isExpanded($offset) {
        return isset($this->expanded[$offset]);
    }
    /**
     * whether or not this collection is expandable
     * @return boolean
     */
    public function isExpandable() {
        return $this->expandable;
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
    public function getFirst() {
        return $this->data[0];
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

            foreach($this->getNormalIterator() as $record) {
                if($value !== null) {
                    $record->rawSet($this->reference_field, $value);
                } else {
                    $record->rawSet($this->reference_field, $this->reference);
                }
            }
        } elseif($relation instanceof Doctrine_Association) {

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
    public function expand($key) {
        $where  = array();
        $params = array();
        $limit  = null;
        $offset = null;

        switch(get_class($this)):
            case "Doctrine_Collection_Offset":
                $limit  = $this->getLimit();
                $offset = (floor($key / $limit) * $limit);

                if( ! $this->expandable && isset($this->expanded[$offset]))
                    return false;

                $fields = implode(", ",$this->table->getColumnNames());
            break;
            default:
                if( ! $this->expandable)
                    return false;

                if( ! isset($this->reference))
                    return false;

                $id = $this->reference->getID();

                if(empty($id))
                    return false;

                switch(get_class($this)):
                    case "Doctrine_Collection_Immediate":
                        $fields = implode(", ",$this->table->getColumnNames());
                    break;
                    default:
                        $fields = implode(", ",$this->table->getPrimaryKeys());
                endswitch;


        endswitch;

        if(isset($this->relation)) {
            if($this->relation instanceof Doctrine_ForeignKey) {
                $params[] = $this->reference->getIncremented();
                $where[] = $this->reference_field." = ?";

                if( ! isset($offset)) {
                    $ids = $this->getPrimaryKeys();

                    if( ! empty($ids)) {
                        $where[] = $this->table->getIdentifier()." NOT IN (".substr(str_repeat("?, ",count($ids)),0,-2).")";
                        $params  = array_merge($params,$ids);
                    }

                    $this->expandable = false;
                }


            } elseif($this->relation instanceof Doctrine_Association) {

                $asf     = $this->relation->getAssociationFactory();
                $query   = "SELECT ".$foreign." FROM ".$asf->getTableName()." WHERE ".$local."=".$this->getIncremented();

                $table = $fk->getTable();
                $graph   = new Doctrine_DQL_Parser($table->getSession());

                $q       = "FROM ".$table->getComponentName()." WHERE ".$table->getComponentName().".".$table->getIdentifier()." IN ($query)";

            }
        }

        $query  = "SELECT ".$fields." FROM ".$this->table->getTableName();

        // apply column aggregation inheritance
        foreach($this->table->getInheritanceMap() as $k => $v) {
            $where[]  = $k." = ?";
            $params[] = $v;
        }
        if( ! empty($where)) {
            $query .= " WHERE ".implode(" AND ",$where);
        }

        $coll   = $this->table->execute($query, $params, $limit, $offset);

        if( ! isset($offset)) {
            foreach($coll as $record) {
                if(isset($this->reference_field))
                    $record->rawSet($this->reference_field,$this->reference);

                $this->reference->addReference($record, $this->relation);
            }
        } else {
            $i = $offset;

            foreach($coll as $record) {
                if(isset($this->reference)) {
                    $this->reference->addReference($record, $this->relation, $i);
                } else
                    $this->data[$i] = $record;

                $i++;
            }

            $this->expanded[$offset] = true;

            // check if the fetched collection's record count is smaller
            // than the query limit, if so this collection has been expanded to its max size

            if(count($coll) < $limit) {
                $this->expandable = false;
            }
        }

        return $coll;
    }
    /**
     * @return boolean
     */
    public function remove($key) {
        if( ! isset($this->data[$key])) {
            $this->expand($key);
            throw new InvalidKeyException();
        }

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
     * @return object Doctrine_Record           return a specified record
     */
    public function get($key) {
        if( ! isset($this->data[$key])) {
            $this->expand($key);

            if( ! isset($this->data[$key]))
                $this->data[$key] = $this->table->create();

            if(isset($this->reference_field)) {
                $value = $this->reference->get($this->relation->getLocal());

                if($value !== null) {
                    $this->data[$key]->rawSet($this->reference_field, $value);
                } else {

                    $this->data[$key]->rawSet($this->reference_field, $this->reference);
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
        $name = $this->table->getIdentifier();

        foreach($this->data as $record):
            if(is_array($record) && isset($record[$name])) {
                $list[] = $record[$name];
            } else {
                $list[] = $record->getIncremented();
            }
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
     *
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
            $record->rawSet($this->reference_field,$this->reference);

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

        if(in_array($record,$this->data)) {
            return false;
        }

        if(isset($key)) {
            if(isset($this->data[$key]))
                return false;

            $this->data[$key] = $record;
            return true;
        }

        if(isset($this->generator)) {
            $key = $this->generator->getIndex($record);
            $this->data[$key] = $record;
        } else
            $this->data[] = $record;

        return true;
    }
    /**
     * populate
     *
     * @param Doctrine_Query $query
     * @param integer $key
     */
    public function populate(Doctrine_Hydrate $query) {
        $name = $this->table->getComponentName();

        if($this instanceof Doctrine_Collection_Immediate ||
           $this instanceof Doctrine_Collection_Offset) {

            $data = $query->getData($name);
            if(is_array($data)) {
                foreach($data as $k=>$v):
                    $this->table->setData($v);
                    $this->add($this->table->getRecord());
                endforeach;
            }
        } elseif($this instanceof Doctrine_Collection_Batch) {
            $this->data = $query->getData($name);

            if(isset($this->generator)) {
                foreach($this->data as $k => $v) {
                    $record = $this->get($k);
                    $i = $this->generator->getIndex($record);
                    $this->data[$i] = $record;
                    unset($this->data[$k]);
                }
            }
        }
    }
    /**
     * getNormalIterator
     * returns normal iterator - an iterator that will not expand this collection
     *
     * @return Doctrine_Iterator_Normal
     */
    public function getNormalIterator() {
        return new Doctrine_Iterator_Normal($this);
    }
    /**
     * save
     * saves all records
     *
     * @return void
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
