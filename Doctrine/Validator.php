<?php
/**
 * Doctrine_Validator
 * Doctrine_Session uses this class for transaction validation
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
        $columns  = $record->getTable()->getColumns();
        $name     = $record->getTable()->getComponentName();

        switch($record->getState()):
            case Doctrine_Record::STATE_TDIRTY:
            case Doctrine_Record::STATE_TCLEAN:
                $data = $record->getData();
            break;
            default:
                $data = $record->getModified();
        endswitch;

        $err      = array();

        foreach($data as $key => $value) {
            if($value === self::$null)
                $value = null;

            $column = $columns[$key];

            if($column[0] == 'array' || $column[0] == 'object') {
                $value = serialize($value);
            }

            if(strlen($value) > $column[1]) {
                $err[$key] = Doctrine_Validator::ERR_LENGTH;
                continue;
            }

            $e = explode("|",$column[2]);

            foreach($e as $k => $arg) {
                if(empty($arg) || $arg == "primary" || $arg == "protected" || $arg == "autoincrement")
                    continue;

                $args = explode(":",$arg);
                if( ! isset($args[1])) 
                    $args[1] = '';

                $validator = self::getValidator($args[0]);
                if( ! $validator->validate($record, $key, $value, $args[1])) {

                    $constant = 'Doctrine_Validator::ERR_'.strtoupper($args[0]);

                    if(defined($constant))
                        $err[$key] = constant($constant);
                    else
                        $err[$key] = Doctrine_Validator::ERR_VALID;

                    // errors found quit validation looping for this column
                    break;
                }
            }

            if(self::gettype($value) !== $column[0] && self::gettype($value) != 'NULL') {
                $err[$key] = Doctrine_Validator::ERR_TYPE;
                continue;
            }
        }

        if( ! empty($err)) {
            $this->stack[$name][] = $err;
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
     * returns the type of loosely typed variable
     *
     * @param mixed $var
     * @return string
     */
    public static function gettype($var) {
        $type = gettype($var);
        switch($type):
            case "string":
                if(preg_match("/^[0-9]+$/",$var)) return "integer";
                elseif(is_numeric($var)) return "float";
                else return $type;
            break;
            default:
            return $type;
        endswitch;
    }
}
?>
