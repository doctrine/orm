<?php
class Doctrine_Validator_Email {
    /**
     * @link http://iamcal.com/publish/articles/php/parsing_email/pdf/
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if(empty($value)) 
            return true;

        $parts = explode("@", $value);
        if(isset($parts[1]) && function_exists("checkdnsrr")) {
            if( ! checkdnsrr($parts[1], "MX"))
                return false;
        }

        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
        $quoted_pair = '\\x5c[\\x00-\\x7f]';
        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
        $domain_ref = $atom;
        $sub_domain = "($domain_ref|$domain_literal)";
        $word = "($atom|$quoted_string)";
        $domain = "$sub_domain(\\x2e$sub_domain)*";
        $local_part = "$word(\\x2e$word)*";
        $addr_spec = "$local_part\\x40$domain";

        return (bool)preg_match("!^$addr_spec$!", $value);
    }
    /**
     * validateEmail
     *
     * method not used anymore (should be removed)
     *
     * @deprecated
     * @param string $value
     *
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
    */
}

