<?php
require_once("Condition.php");

class Doctrine_Query_Having extends Doctrine_Query_Condition {
    /**
     * DQL Aggregate Function parser
     *
     * @param string $func
     * @return mixed
     */
    private function parseAggregateFunction($func) {
        $pos = strpos($func,"(");

        if($pos !== false) {

            $funcs  = array();

            $name   = substr($func, 0, $pos);
            $func   = substr($func, ($pos + 1), -1);
            $params = Doctrine_Query::bracketExplode($func, ",", "(", ")");

            foreach($params as $k => $param) {
                $params[$k] = $this->parseAggregateFunction($param);
            }

            $funcs = $name."(".implode(", ", $params).")";

            return $funcs;

        } else {
            if( ! is_numeric($func)) {
                $a = explode(".",$func);
                $field     = array_pop($a);
                $reference = implode(".",$a);
                $table     = $this->query->load($reference, false);
                $func      = $this->query->getTableAlias($reference).".".$field;

                return $func;
            } else {
                return $func;
            }
        }
    }
    /**
     * load
     * returns the parsed query part
     *
     * @param string $having
     * @return string
     */
    final public function load($having) {
        $e = Doctrine_Query::bracketExplode($having," ","(",")");

        $r = array_shift($e);
        $t = explode("(",$r);

        $count = count($t);
        $r = $this->parseAggregateFunction($r);
        $operator  = array_shift($e);
        $value     = implode(" ",$e);
        $r .= " ".$operator." ".$value;

        return $r;
    }
    /**
     * __toString
     *
     * @return string
     */
    public function __toString() {
        return ( ! empty($this->parts))?implode(" AND ", $this->parts):'';
    }
}

