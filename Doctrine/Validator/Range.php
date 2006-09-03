<?php
class Doctrine_Validator_Range {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        $e = explode("-",$args);
        if($value < $e[0])
            return false;
            
        if(isset($e[1]) && $value > $e[1])
            return false;

        return true;
    }
}

