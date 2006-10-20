<?php
/**
 * oracle driver
 */
class Doctrine_Connection_Oracle extends Doctrine_Connection {
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     */
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
    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @access public
     */
    function now($type = 'timestamp')
    {
        switch ($type) {
        case 'date':
        case 'time':
        case 'timestamp':
        default:
            return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }
    /**
     * substring
     *
     * @return string           SQL substring function with given parameters
     */
    function substring($value, $position = 1, $length = null) {
        if($length !== null)
            return "SUBSTR($value, $position, $length)";

        return "SUBSTR($value, $position)";
    }
    /**
     * random
     *
     * @return string           an oracle SQL string that generates a float between 0 and 1
     */
    function random() {
        return 'dbms_random.value';
    }
}

