<?php
class Doctrine_Validator_HtmlColor {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if( ! preg_match("/^#{0,1}[0-9]{6}$/",$color)) {
            return false;
        }
        return true;
    }
}
?>
