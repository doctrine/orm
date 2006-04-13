<?php
class Doctrine_Validator {
    const ERR_LENGTH = 0;
    const ERR_TYPE   = 1;
    const ERR_VALID  = 2;
    const ERR_UNIQUE = 3;
    
    /**
     * @var array $stack        error stack
     */
    private $stack      = array();
    /**
     * @var array $validators
     */
    private static $validators = array();
    /**
     * @param string $name
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
     * @param Doctrine_Record $record
     * @return void
     */
    public function validateRecord(Doctrine_Record $record) {
        $modified = $record->getModified();
        $columns  = $record->getTable()->getColumns();
        $name     = $record->getTable()->getComponentName();

        $err      = array();
        foreach($modified as $key => $value) {
            $column = $columns[$key];

            if(strlen($value) > $column[1]) {
                $err[$key] = Doctrine_Validator::ERR_LENGTH;
                continue;
            } 

            if(self::gettype($value) !== $column[0]) {
                $err[$key] = Doctrine_Validator::ERR_TYPE;
                continue;
            }

            $e = explode("|",$column[2]);

            foreach($e as $k => $arg) {
                if(empty($arg) || $arg == "primary")
                    continue;

                $validator = self::getValidator($arg);
                if( ! $validator->validate($record,$key,$value)) {
                    switch(strtolower($arg)):
                        case "unique":
                            $err[$key] = Doctrine_Validator::ERR_UNIQUE;
                        break;
                        default:
                            $err[$key] = Doctrine_Validator::ERR_VALID;
                        break;
                    endswitch;
                }
                if(isset($err[$key]))
                    break;
            }
        }

        if( ! empty($err)) {
            $this->stack[$name][] = $err;
            return false;
        }
        
        return true;
    }
    /**
     * @return boolean
     */
    public function hasErrors() {
        return (count($this->stack) > 0);
    }
    /**
     * @return array
     */
    public function getErrorStack() {
        return $this->stack;
    }
    /**
     * @param mixed $var
     */
    public static function gettype($var) {
        $type = gettype($var);
        switch($type):
            case "string":
                if(preg_match("/^[0-9]*$/",$var)) return "integer";
                elseif(is_numeric($var)) return "float";
                else return $type;
            break;
            default:
            return $type;
        endswitch;
    }
}
?>
