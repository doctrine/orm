<?php
class Doctrine_Validator_Notnull {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value) {
        if ($value === null || $value === '')
            return false;
        
        return true;
    }
}

