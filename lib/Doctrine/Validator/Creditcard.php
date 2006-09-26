<?php
class Doctrine_Validator_Creditcard {

    /**
     * @link http://www.owasp.org/index.php/OWASP_Validation_Regex_Repository
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        return preg_match('#^((4\d{3})|(5[1-5]\d{2})|(6011)|(7\d{3}))-?\d{4}-?\d{4}-?\d{4}|3[4,7]\d{13}$#', $value);
    }

}

