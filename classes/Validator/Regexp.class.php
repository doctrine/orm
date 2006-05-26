<?php
class Doctrine_Validator_Regexp {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if(preg_match("/$args/", $value))
            return true;
            
        return false;
    }
}
?>
