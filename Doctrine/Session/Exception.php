<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Exception.php");
/**
 * thrown when user tries to get the current 
 * session and there are no open sessions
 */
class Doctrine_Session_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("There are no opened sessions. Use 
                             Doctrine_Manager::getInstance()->openSession() to open a new session.",Doctrine::ERR_NO_SESSIONS);
    }
}
?>
