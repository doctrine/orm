<?php
/*
 *  $Id: Record.php 1298 2007-05-01 19:26:03Z zYne $
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
 * Doctrine_Record_Filter
 * Filters and prepares the record data
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1298 $
 */
class Doctrine_Record_Filter
{
    /**
     * @var Doctrine_Record $_record        the record object this filter belongs to
     */
    protected $_record;
    /**
     * @var Doctrine_Null $null             a Doctrine_Null object used for extremely fast
     *                                      null value testing
     */
    private static $null;
    /**
     * constructor
     *
     * @param Doctrine_Record $_record      the record object this filter belongs to
     */
    public function __construct(Doctrine_Record $record)
    {
        $this->_record = $record;
    }
    /**
     * getRecord
     *
     * @return Doctrine_Record $_record     the record object this filter belongs to
     */
    public function getRecord()
    {
        return $this->_record;
    }
    /**
     * initNullObject
     *
     * @param Doctrine_Null $null
     * @return void
     */
    public static function initNullObject(Doctrine_Null $null)
    {
        self::$null = $null;
    }
    /**
     * setDefaultValues
     * sets the default values for records internal data
     *
     * @param boolean $overwrite                whether or not to overwrite the already set values
     * @return boolean
     */
    public function assignDefaultValues($data, $overwrite = false)
    {
    	$table = $this->_record->getTable();

        if ( ! $table->hasDefaultValues()) {
            return false;
        }
        $modified = array();
        foreach ($data as $column => $value) {
            $default = $table->getDefaultValueOf($column);

            if ($default === null) {
                $default = self::$null;
            }

            if ($value === self::$null || $overwrite) {
                $this->_record->rawSet($column, $default);
                $modified[]    = $column;
                $this->_record->state(Doctrine_Record::STATE_TDIRTY);
            }
        }
        $this->_record->setModified($modified);
    }
    /**
     * cleanData
     * this method does several things to records internal data
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Gets the appropriate enum values for enum typed columns
     * 4. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     *
     * example:
     *
     * $data = array("name" => "John", "lastname" => null, "id" => 1, "unknown" => "unknown");
     * $data after operation:
     * $data = array("name" => "John", "lastname" => Object(Doctrine_Null));
     *
     * here column 'id' is removed since its auto-incremented primary key (read-only)
     *
     * @throws Doctrine_Record_Exception        if unserialization of array/object typed column fails or
     *                                          if uncompression of gzip typed column fails
     *
     * @param array $data                       data array to be cleaned
     * @return integer
     */
    public function cleanData($data)
    {
        $tmp  = $data;
        $data = array();  

        foreach ($this->_table->getColumnNames() as $name) {
            $type = $this->_table->getTypeOf($name);

            if ( ! isset($tmp[$name])) {
                $data[$name] = self::$null;
            } else {
                switch ($type) {
                    case 'array':
                    case 'object':
                        if ($tmp[$name] !== self::$null) {
                            if (is_string($tmp[$name])) {
                                $value = unserialize($tmp[$name]);

                                if ($value === false) {
                                    throw new Doctrine_Record_Exception('Unserialization of ' . $name . ' failed.');
                                }
                            } else {
                                $value = $tmp[$name];
                            }
                            $data[$name] = $value;
                        }
                        break;
                    case 'gzip':
                        if ($tmp[$name] !== self::$null) {
                            $value = gzuncompress($tmp[$name]);

                            if ($value === false) {
                                throw new Doctrine_Record_Exception('Uncompressing of ' . $name . ' failed.');
                            }
                            
                            $data[$name] = $value;
                        }
                        break;
                    case 'enum':
                        $data[$name] = $this->_table->enumValue($name, $tmp[$name]);
                        break;
                    default:
                        $data[$name] = $tmp[$name];
                }

            }
        }

        return $data;
    }
    /**
     * prepareIdentifiers
     * prepares identifiers for later use
     *
     * @param boolean $exists               whether or not this record exists in persistent data store
     * @return void
     */
    private function prepareIdentifiers($exists = true)
    {
        $id = $this->_table->getIdentifier();
    	$this->_id   = array();
        if (count($id) > 1) {
            foreach ($id as $name) {
                if ($this->_data[$name] === self::$null) {
                    $this->_id[$name] = null;
                } else {
                    $this->_id[$name] = $this->_data[$name];
                }
            }
    	} else {
            if (isset($this->_data[$id]) && $this->_data[$id] !== self::$null) {
                $this->_id[$id] = $this->_data[$id];
            }
        }
    }
    /**
     * getPrepared
     *
     * returns an array of modified fields and values with data preparation
     * adds column aggregation inheritance and converts Records into primary key values
     *
     * @param array $array
     * @return array
     */
    public function getPrepared(array $array = array()) {
        $a = array();

        if (empty($array)) {
            $array = $this->_modified;
        }
        foreach ($array as $k => $v) {
            $type = $this->_table->getTypeOf($v);

            if ($this->_data[$v] === self::$null) {
                $a[$v] = null;
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    $a[$v] = serialize($this->_data[$v]);
                    break;
                case 'gzip':
                    $a[$v] = gzcompress($this->_data[$v],5);
                    break;
                case 'boolean':
                    $a[$v] = $this->getTable()->getConnection()->convertBooleans($this->_data[$v]);
                break;
                case 'enum':
                    $a[$v] = $this->_table->enumIndex($v,$this->_data[$v]);
                    break;
                default:
                    if ($this->_data[$v] instanceof Doctrine_Record) {
                        $this->_data[$v] = $this->_data[$v]->getIncremented();
                    }

                    $a[$v] = $this->_data[$v];
            }
        }
        $map = $this->_table->inheritanceMap;
        foreach ($map as $k => $v) {
            $old = $this->get($k, false);

            if ((string) $old !== (string) $v || $old === null) {
                $a[$k] = $v;
                $this->_data[$k] = $v;
            }
        }

        return $a;
    }
}
