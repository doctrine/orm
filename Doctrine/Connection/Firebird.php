<?php
/**
 * firebird driver
 */
class Doctrine_Connection_Firebird extends Doctrine_Connection {
    public function modifyLimitQuery($query,$limit,$offset) {
        return preg_replace('/^([\s(])*SELECT(?!\s*FIRST\s*\d+)/i',
                "SELECT FIRST $limit SKIP $offset", $query);
    }
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $stmt = $this->query("SELECT UNIQUE FROM ".$sequence);
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
}

