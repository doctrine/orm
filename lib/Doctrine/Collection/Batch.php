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
Doctrine::autoload('Doctrine_Collection');
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
     * @return boolean
     */
    public function setBatchSize($batchSize) {
        $batchSize = (int) $batchSize;
        if($batchSize <= 0)
            return false;

        $this->batchSize = $batchSize;
        return true;
    }
    /**
     * returns the batch size of this collection
     *
     * @return integer
     */
    public function getBatchSize() {
        return $this->batchSize;
    }
    /**
     * load                                        
     * loads a specified element, by loading the batch the element is part of
     *
     * @param Doctrine_Record $record              record to be loaded
     * @return boolean                             whether or not the load operation was successful
     */
    public function load(Doctrine_Record $record) {
        if(empty($this->data))
            return false;

        $id  = $record->obtainIdentifier();
        $identifier = $this->table->getIdentifier();
        foreach($this->data as $key => $v) {
            if(is_object($v)) {
                if($v->obtainIdentifier() == $id)
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
                if($this->data[$i] instanceof Doctrine_Record)
                    $id = $this->data[$i]->getIncremented();
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

            $stmt  = $this->table->getConnection()->execute($query,array_values($a));

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
            $this->data[$key]->set($this->reference_field, $this->reference, false);


        return $this->data[$key];
    }
    /**
     * @return Doctrine_Iterator
     */
    public function getIterator() {
        return new Doctrine_Collection_Iterator_Expandable($this);
    }
}

