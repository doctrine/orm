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
     * returns the regular expression operator 
     * (implemented by the connection drivers)
     *
     * @return string
     */
    public function getRegexpOperator() {
        return 'SIMILAR TO';
    }
}

