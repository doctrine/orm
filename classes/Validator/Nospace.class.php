<?php
class Doctrine_Validator_NoSpace {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value) {
        if(preg_match("/[\s\r\t\n]/", $value))
            return false;

        return true;
    }
}
?>
