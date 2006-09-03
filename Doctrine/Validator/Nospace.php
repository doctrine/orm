<?php
class Doctrine_Validator_NoSpace {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if(trim($value) === '')
            return false;

        return true;
    }
}

