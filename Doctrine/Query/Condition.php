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

        $parts = Doctrine_Query::bracketExplode($str," && ","(",")");
        if(count($parts) > 1) {
            $ret = array();
            foreach($parts as $part) {
                $part = Doctrine_Query::bracketTrim($part, "(", ")");
                $ret[] = $this->parse($part);
            }
            $r = implode(" AND ",$ret);
        } else {

            $parts = Doctrine_Query::bracketExplode($str," || ","(",")");
            if(count($parts) > 1) {
                $ret = array();
                foreach($parts as $part) {
                    $part = Doctrine_Query::bracketTrim($part, "(", ")");
                    $ret[] = $this->parse($part);
                }
                $r = implode(" OR ",$ret);
            } else {
                if(substr($parts[0],0,1) == "(" && substr($parts[0],-1) == ")") 
                    return $this->parse(substr($parts[0],1,-1));
                else
                    return $this->load($parts[0]);
            }
        }

        return "(".$r.")";
    }
}

