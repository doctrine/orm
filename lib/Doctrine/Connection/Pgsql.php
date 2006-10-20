<?php
require_once("Common.php");
/**
 * pgsql driver
 */
class Doctrine_Connection_Pgsql extends Doctrine_Connection_Common {
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $stmt = $this->query("SELECT NEXTVAL('$sequence')");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * getRegexpOperator
     *
     * @return string           the regular expression operator
     */
    public function getRegexpOperator() {
        return 'SIMILAR TO';
    }
    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return return string to generate float between 0 and 1
     * @access public
     */
    public function random() {
        return 'RANDOM()';
    }
}

