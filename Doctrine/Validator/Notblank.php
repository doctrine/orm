<?php
class Doctrine_Validator_Notblank {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        return (trim($value) != ""); 
    }
}
?>
