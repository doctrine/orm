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
        if(is_array($args)) {
            foreach($args as $regexp) {
                if( ! preg_match($args, $value))
                    return false;
            }
            return true;
        } else {
            if(preg_match($args, $value))
                return true;
        }

        return false;
    }
}

