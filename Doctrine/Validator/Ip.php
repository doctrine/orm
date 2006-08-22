<?php
class Doctrine_Validator_Ip {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        return (bool) ip2long(str_replace("\0", '', $value));
    }
}
?>
