<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Exception.class.php");
/**
 * thrown when user defined Doctrine_Table is badly named
 */
class Doctrine_Naming_Exception extends Doctrine_Exception {
    public function __construct() {
        parent::__construct("Badly named Doctrine_Table. Each Doctrine_Table
                             must be in format [Name]Table.", Doctrine::ERR_NAMING);
    }
}
?>
