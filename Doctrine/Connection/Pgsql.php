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
}
?>
