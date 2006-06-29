<?php
require_once("Part.php");

class Doctrine_Query_From extends Doctrine_Query_Part {

    /**
     * DQL FROM PARSER
     * parses the from part of the query string

     * @param string $str
     * @return void
     */
    final public function parse($str) {
        foreach(Doctrine_Query::bracketExplode(trim($str),",", "(",")") as $reference) {
            $reference = trim($reference);
            $a         = explode(".",$reference);
            $field     = array_pop($a);
            $table     = $this->query->load($reference);
        }
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(", ", $this->parts):'';
    }
}
?>
