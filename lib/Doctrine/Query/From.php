<?php
Doctrine::autoload("Doctrine_Query_Part");

class Doctrine_Query_From extends Doctrine_Query_Part {

    /**
     * DQL FROM PARSER
     * parses the from part of the query string

     * @param string $str
     * @return void
     */
    final public function parse($str) {
        $str = trim($str);
        $parts = Doctrine_Query::bracketExplode($str, 'JOIN');

        $operator = false;
        $last = '';

        foreach($parts as $k => $part) {
            $part = trim($part);
            $e    = explode(" ", $part);

            if(end($e) == 'INNER' || end($e) == 'LEFT')
                $last = array_pop($e);

            $part = implode(" ", $e);

            foreach(Doctrine_Query::bracketExplode($part, ',') as $reference) {
                $reference = trim($reference);
                $e         = explode('.', $reference);

                if($operator) {
                    $reference = array_shift($e).$operator.implode('.', $e);
                }
                $table     = $this->query->load($reference);
            }                                              
            
            $operator = ($last == 'INNER') ? ':' : '.';
        }
    }

    public function __toString() {
        return ( ! empty($this->parts))?implode(", ", $this->parts):'';
    }
}

