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
        if(isset($args[0]) && $value < $args[0])
            return false;

        if(isset($args[1]) && $value > $args[1])
            return false;

        return true;
    }
}

