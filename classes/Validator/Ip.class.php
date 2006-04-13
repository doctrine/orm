<?php
class Doctrine_Validator_IP {
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value) {
        $e = explode(".",$request);
        if(count($e) != 4) return false;

        foreach($e as $k=>$v):
            if(! is_numeric($v)) return false;
            $v = (int) $v;
            if($v < 0 || $v > 255) return false;
        endforeach;
        return true;
    }
}
?>
