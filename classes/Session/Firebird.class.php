<?php
/**
 * firebird driver
 */
class Doctrine_Session_Firebird extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit,$offset) {
        return preg_replace("/([\s(])*SELECT/i","\\1SELECT TOP($from, $count)", $query);
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
?>
