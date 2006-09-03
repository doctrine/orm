<?php
require_once("Part.php");

class Doctrine_Query_Orderby extends Doctrine_Query_Part {
    /**
     * DQL ORDER BY PARSER
     * parses the order by part of the query string
     *
     * @param string $str
     * @return void
     */
    final public function parse($str) {
        $ret = array();

        foreach(explode(",",trim($str)) as $r) {
            $r = trim($r);
            $e = explode(" ",$r);
            $a = explode(".",$e[0]);
    
            if(count($a) > 1) {
                $field     = array_pop($a);
                $reference = implode(".",$a);
                $name      = end($a);

                $this->query->load($reference, false);
                $alias     = $this->query->getTableAlias($reference);
                $tname     = $this->query->getTable($alias)->getTableName();

                $r = $tname.".".$field;
                if(isset($e[1])) 
                    $r .= " ".$e[1];
            }
            $ret[] = $r;
        }
        return implode(", ", $ret);
    }
    public function __toString() {
        return ( ! empty($this->parts))?implode(", ", $this->parts):'';
    }
}

