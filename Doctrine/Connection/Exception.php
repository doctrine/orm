<?php
Doctrine::autoload('Doctrine_Exception');
/**
 * thrown when user tries to get the current 
 * connection and there are no open connections
 */
class Doctrine_Connection_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("There are no opened connections. Use 
                             Doctrine_Manager::getInstance()->openConnection() to open a new connection.",Doctrine::ERR_NO_SESSIONS);
    }
}
?>
