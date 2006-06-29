<?php
require_once("Part.php");

abstract class Doctrine_Query_Condition extends Doctrine_Query_Part {
    /**
     * DQL CONDITION PARSER
     * parses the where/having part of the query string
     *
     *
     * @param string $str
     * @return string
     */
    final public function parse($str) {

        $tmp = trim($str);
        $str = Doctrine_Query::bracketTrim($tmp,"(",")");
        
        $brackets = false;

        while($tmp != $str) {
            $brackets = true;
            $tmp = $str;
            $str = Doctrine_Query::bracketTrim($str,"(",")");
        }

        $parts = Doctrine_Query::bracketExplode($str," && ","(",")");
        if(count($parts) > 1) {
            $ret = array();
            foreach($parts as $part) {
                $ret[] = $this->parse($part, $type);
            }
            $r = implode(" AND ",$ret);
        } else {
            $parts = Doctrine_Query::bracketExplode($str," || ","(",")");
            if(count($parts) > 1) {
                $ret = array();
                foreach($parts as $part) {
                    $ret[] = $this->parse($part);
                }
                $r = implode(" OR ",$ret);
            } else {
                return $this->load($parts[0]);
            }
        }
        if($brackets)
            return "(".$r.")";
        else
            return $r;
    }
}
?>
