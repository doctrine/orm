<?php
class Doctrine_Validator_Range {
    /**
     * @param integer $max
     */
    public function setMin($min) {
        $this->min = $min;
    }
    /**
     * @param integer $max
     */
    public function setMax($max) {
        $this->max = $max;
    }
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        if($var < $this->min)
            return false;

        if($var > $this->max)
            return false;

        return true;
    }
}
?>
