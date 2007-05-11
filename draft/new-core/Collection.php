<?php
/*
 *  $Id: Collection.php 1207 2007-03-31 19:49:23Z zYne $
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
Doctrine::autoload("Doctrine_Access");
/**
 * Doctrine_Collection
 * Collection of Doctrine_Record objects.
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1207 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Collection2 extends Doctrine_Collection implements Countable, IteratorAggregate, Serializable
{
    /**
     * @var array $data                     an array containing the data access objects of this collection
     */
    protected $data = array();
    /**
     * @var Doctrine_Table $table           each collection has only records of specified table
     */
    protected $_table;
    /**
     * @var Doctrine_Record $reference      collection can belong to a record
     */
    protected $reference;
    /**
     * @var string $referenceField         the reference field of the collection
     */
    protected $referenceField;
    /**
     * @var Doctrine_Relation               the record this collection is related to, if any
     */
    protected $relation;
    /**
     * @var string $keyColumn               the name of the column that is used for collection key mapping
     */
    protected $keyColumn;
    /**
     * @var Doctrine_Null $null             used for extremely fast null value testing
     */
    protected static $null;

    protected $aggregateValues = array();

    /**
     * constructor
     *
     * @param Doctrine_Table|string $table
     */
    public function __construct($table)
    {
        if ( ! ($table instanceof Doctrine_Table)) {
            $table = Doctrine_Manager::getInstance()
                        ->getTable($table);
        }
        $this->_table = $table;

        $name = $table->getAttribute(Doctrine::ATTR_COLL_KEY);
        if ($name !== null) {
            $this->keyColumn = $name;
        }
    }
    /**
     * initNullObject
     * initializes the null object for this collection
     *
     * @return void
     */
    public static function initNullObject(Doctrine_Null $null)
    {
        self::$null = $null;
    }
    /**
     * getTable
     * returns the table this collection belongs to
     *
     * @return Doctrine_Table
     */
    public function getTable()
    {
        return $this->_table;
    }
    /**
     * this method is automatically called when this Doctrine_Collection is serialized
     *
     * @return array
     */
    public function serialize()
    {
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
    public function unserialize($serialized)
    {
        $manager    = Doctrine_Manager::getInstance();
        $connection    = $manager->getCurrentConnection();

        $array = unserialize($serialized);

        foreach ($array as $name => $values) {
            $this->$name = $values;
        }

        $this->_table        = $connection->getTable($this->_table);

        $this->expanded     = array();
        $this->expandable   = true;

        $name = $this->_table->getAttribute(Doctrine::ATTR_COLL_KEY);
        if ($name !== null) {
            $this->keyColumn = $name;
        }
    }
    /**
     * setKeyColumn
     *
     * @param string $column
     * @return void
     */
    public function setKeyColumn($column)
    {
        $this->keyColumn = $column;
    }
    /**
     * getKeyColumn
     * returns the name of the key column
     *
     * @return string
     */
    public function getKeyColumn()
    {
        return $this->column;
    }
    /**
     * returns all the records as an array
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * @param array $data
     */
    public function addData(array $data)
    {
        $this->data[] = $data;
    }
    /**
     * getFirst
     * returns the first record in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        return reset($this->data);
    }
    /**
     * getLast
     * returns the last record in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        return end($this->data);
    }
    /**
     * setReference
     * sets a reference pointer
     *
     * @return void
     */
    public function setReference(Doctrine_Record $record, Doctrine_Relation $relation)
    {
        $this->reference       = $record;
        $this->relation        = $relation;

        if ($relation instanceof Doctrine_Relation_ForeignKey
           || $relation instanceof Doctrine_Relation_LocalKey
        ) {

            $this->referenceField = $relation->getForeign();

            $value = $record->get($relation->getLocal());

            foreach ($this->getNormalIterator() as $record) {
                if ($value !== null) {
                    $record->set($this->referenceField, $value, false);
                } else {
                    $record->set($this->referenceField, $this->reference, false);
                }
            }
        } elseif ($relation instanceof Doctrine_Relation_Association) {

        }
    }
    /**
     * getReference
     *
     * @return mixed
     */
    public function getReference()
    {
        return $this->reference;
    }
    /**
     * remove
     * removes a specified collection element
     *
     * @param mixed $key
     * @return boolean
     */
    public function remove($key)
    {
        if ( ! isset($this->data[$key])) {
            $this->expand($key);

            throw new Doctrine_Collection_Exception('Unknown key ' . $key);
        }

        $removed = $this->data[$key];

        unset($this->data[$key]);
        return $removed;
    }
    /**
     * contains
     * whether or not this collection contains a specified element
     *
     * @param mixed $key                    the key of the element
     * @return boolean
     */
    public function contains($key)
    {
        return isset($this->data[$key]);
    }
    /**
     * get
     * returns a record for given key
     *
     * There are two special cases:
     *
     * 1. if null is given as a key a new record is created and attached
     * at the end of the collection
     *
     * 2. if given key does not exist, then a new record is create and attached
     * to the given key
     *
     * Collection also maps referential information to newly created records
     *
     * @param mixed $key                    the key of the element
     * @return Doctrine_Record              return a specified record
     */
    public function get($key)
    {
    	if ($key === null || ! isset($this->data[$key])) {
    	    $record = $this->_table->create();

            if (isset($this->referenceField)) {
                $record->set($this->referenceField, $this->reference, false);
            }

            $this->data[] = $record;

            return $record;
    	}

        return $this->data[$key];
    }

    /**
     * @return array                an array containing all primary keys
     */
    public function getPrimaryKeys()
    {
        $list = array();
        $name = $this->_table->getIdentifier();

        foreach ($this->data as $record) {
            if (is_array($record) && isset($record[$name])) {
                $list[] = $record[$name];
            } else {
                $list[] = $record->getIncremented();
            }
        }
        return $list;
    }
    /**
     * returns all keys
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->data);
    }
    /**
     * count
     * this class implements interface countable
     * returns the number of records in this collection
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }
    /**
     * set
     * @param integer $key
     * @param Doctrine_Record $record
     * @return void
     */
    public function set($key, Doctrine_Record $record)
    {
        if (isset($this->referenceField)) {
            $record->set($this->referenceField, $this->reference, false);
        }
        $this->data[$key] = $record;
    }
    /**
     * adds a record to collection
     * @param Doctrine_Record $record              record to be added
     * @param string $key                          optional key for the record
     * @return boolean
     */
    public function add(Doctrine_Record $record, $key = null)
    {
        if (isset($this->referenceField)) {
            $record->set($this->referenceField, $this->reference, false);
        }
        /**
         * for some weird reason in_array cannot be used here (php bug ?)
         *
         * if used it results in fatal error : [ nesting level too deep ]
         */
        foreach ($this->data as $val) {
            if ($val === $record) {
                return false;
            }
        }

        if (isset($key)) {
            if (isset($this->data[$key])) {
                return false;
            }
            $this->data[$key] = $record;
            return true;
        }

        if (isset($this->keyColumn)) {
            $value = $record->get($this->keyColumn);
            if ($value === null) {
                throw new Doctrine_Collection_Exception("Couldn't create collection index. Record field '".$this->keyColumn."' was null.");
            }
            $this->data[$value] = $record;
        } else {
            $this->data[] = $record;
        }
        return true;
    }
    /**
     * loadRelated
     *
     * @param mixed $name
     * @return boolean
     */
    public function loadRelated($name = null)
    {
        $list = array();
        $query   = new Doctrine_Query($this->_table->getConnection());

        if ( ! isset($name)) {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            };
            $query->from($this->_table->getComponentName() . '(' . implode(", ",$this->_table->getPrimaryKeys()) . ')');
            $query->where($this->_table->getComponentName() . '.id IN (' . substr(str_repeat("?, ", count($list)),0,-2) . ')');

            return $query;
        }

        $rel     = $this->_table->getRelation($name);

        if ($rel instanceof Doctrine_Relation_LocalKey || $rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->data as $record) {
                $list[] = $record[$rel->getLocal()];
            }
        } else {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            }
        }

        $dql     = $rel->getRelationDql(count($list), 'collection');

        $coll    = $query->query($dql, $list);

        $this->populateRelated($name, $coll);
    }
    /**
     * populateRelated
     *
     * @param string $name
     * @param Doctrine_Collection $coll
     * @return void
     */
    public function populateRelated($name, Doctrine_Collection $coll)
    {
        $rel     = $this->_table->getRelation($name);
        $table   = $rel->getTable();
        $foreign = $rel->getForeign();
        $local   = $rel->getLocal();

        if ($rel instanceof Doctrine_Relation_LocalKey) {
            foreach ($this->data as $key => $record) {
                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $this->data[$key]->setRelated($name, $related);
                    }
                }
            }
        } elseif ($rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->data as $key => $record) {
                if ($record->state() == Doctrine_Record::STATE_TCLEAN
                   || $record->state() == Doctrine_Record::STATE_TDIRTY
                ) {
                    continue;
                }
                $sub = new Doctrine_Collection($table);

                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $sub->add($related);
                        $coll->remove($k);
                    }
                }

                $this->data[$key]->setRelated($name, $sub);
            }
        } elseif ($rel instanceof Doctrine_Relation_Association) {
            $identifier = $this->_table->getIdentifier();
            $asf        = $rel->getAssociationFactory();
            $name       = $table->getComponentName();

            foreach ($this->data as $key => $record) {
                if ($record->state() == Doctrine_Record::STATE_TCLEAN
                   || $record->state() == Doctrine_Record::STATE_TDIRTY
                ) {
                    continue;
                }
                $sub = new Doctrine_Collection($table);
                foreach ($coll as $k => $related) {
                    if ($related->get($local) == $record[$identifier]) {
                        $sub->add($related->get($name));
                    }
                }
                $this->data[$key]->setRelated($name, $sub);

            }
        }
    }
    /**
     * getNormalIterator
     * returns normal iterator - an iterator that will not expand this collection
     *
     * @return Doctrine_Iterator_Normal
     */
    public function getNormalIterator()
    {
        return new Doctrine_Collection_Iterator_Normal($this);
    }
    /**
     * save
     * saves all records of this collection
     *
     * @return void
     */
    public function save(Doctrine_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        $conn->beginTransaction();

        foreach ($this as $key => $record) {
            $record->save($conn);
        }

        $conn->commit();
    }
    /**
     * single shot delete
     * deletes all records from this collection
     * and uses only one database query to perform this operation
     *
     * @return boolean
     */
    public function delete(Doctrine_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }

        $conn->beginTransaction();

        foreach ($this as $key => $record) {
            $record->delete($conn);
        }

        $conn->commit();

        $this->data = array();
    }
    /**
     * getIterator
     * @return object ArrayIterator
     */
    public function getIterator()
    {
        $data = $this->data;
        return new ArrayIterator($data);
    }
    /**
     * returns a string representation of this object
     */
    public function __toString()
    {
        return Doctrine_Lib::getCollectionAsString($this);
    }
}
