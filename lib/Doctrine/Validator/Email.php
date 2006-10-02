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

        if(isset($args[0])) {
            $parts = explode("@", $value);
            if(isset($parts[1]) && function_exists("checkdnsrr")) {
                if( ! checkdnsrr($parts[1], "MX"))
                    return false;
            }
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
        $domain = "$sub_domain(\\x2e$sub_domain)+";
        $local_part = "$word(\\x2e$word)*";
        $addr_spec = "$local_part\\x40$domain";

        return (bool)preg_match("!^$addr_spec$!", $value);
    }

}

