<?php
class Doctrine_Validator_Date {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if(empty($value))
            return true;

        $e = explode("-", $value);
        if(count($e) !== 3) 
            return false;

        return checkdate($e[1], $e[0], $e[2]);
    }
}

