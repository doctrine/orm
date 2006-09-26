<?php
class Doctrine_Validator_Enum {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        $max = substr_count($args, "-");
        $int = (int) $value;

        if($int != $value)
            return false;

        if($int < 0 || $int > $max)
            return false;
            
        return true;
    }
}

