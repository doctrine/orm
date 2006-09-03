<?php 
require_once("Part.php");

class Doctrine_Query_Groupby extends Doctrine_Query_Part {
    /**
     * DQL GROUP BY PARSER
     * parses the group by part of the query string

     * @param string $str
     * @return void
     */
    final public function parse($str) {
        $r = array();
        foreach(explode(",", $str) as $reference) {
            $reference = trim($reference);
            $e     = explode(".",$reference);
            $field = array_pop($e);
            $ref   = implode(".", $e);
            $table = $this->query->load($ref);
            $component = $table->getComponentName();
            $r[] = $this->query->getTableAlias($ref).".".$field;
        }
        return implode(", ", $r);
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(", ", $this->parts):'';
    }
}

