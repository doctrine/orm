<?php
class Doctrine_Validator_Email {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if(empty($value)) 
            return true;

        return self::validateEmail($value);
    }
    /**
     * validateEmail
     *
     * @param string $value
     */
    public static function validateEmail($value) {
        $parts = explode("@", $value);

        if(count($parts) != 2) 
            return false;

        if(strlen($parts[0]) < 1 || strlen($parts[0]) > 64)
            return false;

        if(strlen($parts[1]) < 1 || strlen($parts[1]) > 255)
            return false;

        $local_array = explode(".", $parts[0]);
        for ($i = 0; $i < sizeof($local_array); $i++) {
            if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $parts[$i])) {
                return false;
            }
        }
        if (!ereg("^\[?[0-9\.]+\]?$", $parts[1])) { // Check if domain is IP. If not, it should be valid domain name
            $domain_array = explode(".", $parts[1]);
            if (count($domain_array) < 2) {
                return false; // Not enough parts to domain
            }
            for ($i = 0; $i < sizeof($domain_array); $i++) {
                if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
                    return false;
                }
            }
        }
        if(function_exists("checkdnsrr")) {
            if( ! checkdnsrr($parts[1], "MX"))
                return false;
        }

        return true;
    }
}

