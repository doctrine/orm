<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Session.class.php");
/**
 * oracle driver
 */
class Doctrine_Session_Oracle extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit,$offset) {
        $e      = explode("select ",strtolower($query));
        $e2     = explode(" from ",$e[1]);
        $fields = $e2[0];

        $query = "SELECT $fields FROM (SELECT rownum as linenum, $fields FROM ($query) WHERE rownum <= ($offset + $limit)) WHERE linenum >= ".++$offset;
        return $query;
    }
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $stmt = $this->query("SELECT $sequence.nextval FROM dual");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
}
?>
