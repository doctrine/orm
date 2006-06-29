<?php
require_once("Condition.php");

class Doctrine_Query_Where extends Doctrine_Query_Condition {
    /**
     * loadWhere
     * returns the parsed query part
     *
     * @param string $where
     * @return string
     */
    final public function load($where) {
        $e = explode(" ",$where);
        $r = array_shift($e);
        $a = explode(".",$r);


        if(count($a) > 1) {
            $field     = array_pop($a);
            $operator  = array_shift($e);
            $value     = implode(" ",$e);
            $reference = implode(".",$a);
            $count     = count($a);

            $table     = $this->query->load($reference, false);
            $where     = $this->query->getTableAlias($reference).".".$field." ".$operator." ".$value;
        }
        return $where;
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(" AND ", $this->parts):'';
    }
}
?>
