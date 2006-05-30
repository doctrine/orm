<?php
Doctrine::autoload('Doctrine_Session');
/**
 * mssql driver
 */
class Doctrine_Session_Mssql extends Doctrine_Session {
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $this->query("INSERT INTO $sequence (vapor) VALUES (0)");
        $stmt = $this->query("SELECT @@IDENTITY FROM $sequence");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
}
?>
