<?php
require_once("Common.php");
/**
 * mysql driver
 */
class Doctrine_Connection_Mysql extends Doctrine_Connection_Common {

    /**
     * the constructor
     * @param PDO $pdo  -- database handle
     */
    public function __construct(Doctrine_Manager $manager,PDO $pdo) {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->setAttribute(Doctrine::ATTR_QUERY_LIMIT, Doctrine::LIMIT_ROWS);
        parent::__construct($manager,$pdo);
    }    
    /**
     * returns the regular expression operator 
     * (implemented by the connection drivers)
     *
     * @return string
     */
    public function getRegexpOperator() {
        return 'RLIKE';
    }
    /**
     * Returns string to concatenate two or more string parameters
     *
     * @param string $value1
     * @param string $value2
     * @param string $values...
     * @return string               a concatenation of two or more strings
     */
    public function concat($value1, $value2) {
        $args = func_get_args();
        return "CONCAT(".implode(', ', $args).")";
    }
}

