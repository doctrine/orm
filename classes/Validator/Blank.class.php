<?php
class Doctrine_Validator_Blank {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value) {
        $string = str_replace("\n","",$value);
        $string = str_replace("\r","",$string);
        $string = str_replace("\t","",$string);
        $string = str_replace("\s","",$string);
        $string = str_replace(" ","",$string);
        if($string == "") return true;
    }
}
?>
