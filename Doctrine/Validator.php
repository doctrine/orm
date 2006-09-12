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
/**
 * Doctrine_Validator
 * Doctrine_Validator performs validations in record properties
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Validator {
    /**
     * ERROR CONSTANTS
     */

    /**
     * constant for length validation error
     */
    const ERR_LENGTH    = 0;
    /**
     * constant for type validation error
     */
    const ERR_TYPE      = 1;
    /**
     * constant for general validation error
     */
    const ERR_VALID     = 2;
    /**
     * constant for unique validation error
     */
    const ERR_UNIQUE    = 3;
    /**
     * constant for blank validation error
     */
    const ERR_NOTBLANK  = 4;
    /**
     * constant for date validation error
     */
    const ERR_DATE      = 5;
    /**
     * constant for null validation error
     */
    const ERR_NOTNULL   = 6;
    /**
     * constant for enum validation error
     */
    const ERR_ENUM      = 7;
    /**
     * constant for range validation error
     */
    const ERR_RANGE     = 8;
    /**
     * constant for regexp validation error
     */
    const ERR_REGEXP    = 9;


    
    /**
     * @var array $stack                error stack
     */
    private $stack      = array();
    /**
     * @var array $validators           an array of validator objects
     */
    private static $validators = array();
    /**
     * @var Doctrine_Null $null         a Doctrine_Null object used for extremely fast
     *                                  null value testing
     */
    private static $null;
    /**
     * initNullObject
     *
     * @param Doctrine_Null $null
     * @return void
     */
    public static function initNullObject(Doctrine_Null $null) {
        self::$null = $null;
    }
    /**
     * returns a validator object
     *
     * @param string $name
     * @return Doctrine_Validator_Interface
     */
    public static function getValidator($name) {
        if( ! isset(self::$validators[$name])) {
            $class = "Doctrine_Validator_".ucwords(strtolower($name));
            if(class_exists($class)) {
                self::$validators[$name] = new $class;
            } elseif(class_exists($name."Validator")) {
                self::$validators[$name] = new $name."Validator";
            } else 
                throw new Doctrine_Exception("Validator named '$name' not availible.");
        }
        return self::$validators[$name];
    }
    /**
     * validates a given record and saves possible errors
     * in Doctrine_Validator::$stack
     *
     * @param Doctrine_Record $record
     * @return void
     */
    public function validateRecord(Doctrine_Record $record) {
        $columns   = $record->getTable()->getColumns();
        $component = $record->getTable()->getComponentName();

        switch($record->getState()):
            case Doctrine_Record::STATE_TDIRTY:
            case Doctrine_Record::STATE_TCLEAN:
                // all fields will be validated
                $data = $record->getData();
            break;
            default:
                // only the modified fields will be validated
                $data = $record->getModified();
        endswitch;

        $err      = array();
        foreach($data as $key => $value) {
            if($value === self::$null)
                $value = null;

            $column = $columns[$key];
            
            if($column[0] == "enum") {
                $value = $record->getTable()->enumIndex($key, $value);

                if($value === false) {
                    $err[$key] = Doctrine_Validator::ERR_ENUM;
                    continue;
                }
            }

            if($column[0] == "array" || $column[0] == "object")
                $length = strlen(serialize($value));
            else
                $length = strlen($value);

            if($length > $column[1]) {
                $err[$key] = Doctrine_Validator::ERR_LENGTH;
                continue;
            }
            if( ! is_array($column[2]))
                $e = explode("|",$column[2]);
            else
                $e = $column[2];


            foreach($e as $k => $arg) {
                if(is_string($k)) {
                    $name = $k;
                    $args = $arg;
                } else {
                    $args = explode(":",$arg);
                    $name = array_shift($args);
                    if( ! isset($args[0]))
                        $args[0] = '';
                }

                if(empty($name) || $name == "primary" || $name == "protected" || $name == "autoincrement")
                    continue;

                $validator = self::getValidator($name);
                if( ! $validator->validate($record, $key, $value, $args)) {

                    $constant = 'Doctrine_Validator::ERR_'.strtoupper($name);

                    if(defined($constant))
                        $err[$key] = constant($constant);
                    else
                        $err[$key] = Doctrine_Validator::ERR_VALID;

                    // errors found quit validation looping for this column
                    break;
                }
            }
            if( ! self::isValidType($value, $column[0])) {
                $err[$key] = Doctrine_Validator::ERR_TYPE;
                continue;
            }
        }

        if( ! empty($err)) {
            $this->stack[$component][] = $err;
            return false;
        }
        
        return true;
    }
    /**
     * whether or not this validator has errors
     *
     * @return boolean
     */
    public function hasErrors() {
        return (count($this->stack) > 0);
    }
    /**
     * returns the error stack
     *
     * @return array
     */
    public function getErrorStack() {
        return $this->stack;
    }
    /**
     * converts a doctrine type to native php type
     *
     * @param $doctrineType
     * @return string
     */
    public static function phpType($doctrineType) {
        switch($doctrineType) {
            case 'enum':
                return 'integer';
            case 'blob':
            case 'clob':
            case 'mbstring':
            case 'timestamp':
            case 'date':
                return 'string';
            break;
            default:
                return $doctrineType;
        }
    }
    /**
     * returns whether or not the given variable is
     * valid type
     *
     * @param mixed $var
     * @param string $type
     * @return boolean
     */
    public static function isValidType($var, $type) {
        if($type == 'boolean')
            return true;

        $looseType = self::gettype($var);
        $type      = self::phpType($type); 

        switch($looseType):
            case 'float':
            case 'double':
            case 'integer':
                if($type == 'string' || $type == 'float')
                    return true;
            case 'string':
            case 'array':
            case 'object':
                return ($type === $looseType);
            break;
            case 'NULL':
                return true;
            break;
        endswitch;
    }
    /**
     * returns the type of loosely typed variable
     *
     * @param mixed $var
     * @return string
     */
    public static function gettype($var) {
        $type = gettype($var);
        switch($type):
            case 'string':
                if(preg_match("/^[0-9]+$/",$var)) 
                    return 'integer';
                elseif(is_numeric($var)) 
                    return 'float';
                else 
                    return $type;
            break;
            default:
                return $type;
        endswitch;
    }
}

