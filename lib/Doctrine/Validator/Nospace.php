<?php
class Doctrine_Validator_Nospace {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        return ($value === null || ! preg_match('/\s\t\r\n/',$value));
    }
}

