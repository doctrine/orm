<?php
class Doctrine_Validator_Date {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value) {
        return checkdate($value);
    }
}
?>
